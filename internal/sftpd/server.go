package sftpd

import (
	"context"
	"crypto/ed25519"
	"crypto/rand"
	"crypto/x509"
	"encoding/binary"
	"encoding/pem"
	"errors"
	"fmt"
	"io/fs"
	"log"
	"net"
	"os"
	"path/filepath"
	"strconv"
	"time"

	"github.com/pkg/sftp"
	"golang.org/x/crypto/ssh"
)

const (
	maxConns         = 100
	handshakeTimeout = 30 * time.Second
)

type Config struct {
	Addr        string // ex: ":2022"
	HostKeyPath string
	DataDir     string // folder induk; isinya <uuid>/ per server
}

type Server struct {
	cfg  Config
	auth Authenticator
	ssh  *ssh.ServerConfig
	sem  chan struct{}
}

func New(cfg Config, auth Authenticator) (*Server, error) {
	signer, err := loadOrCreateHostKey(cfg.HostKeyPath)
	if err != nil {
		return nil, fmt.Errorf("host key: %w", err)
	}

	s := &Server{cfg: cfg, auth: auth, sem: make(chan struct{}, maxConns)}
	s.ssh = &ssh.ServerConfig{
		MaxAuthTries:     3,
		PasswordCallback: s.passwordCallback,
	}
	s.ssh.AddHostKey(signer)
	return s, nil
}

func loadOrCreateHostKey(path string) (ssh.Signer, error) {
	b, err := os.ReadFile(path)
	if err == nil {
		return ssh.ParsePrivateKey(b)
	}
	if !errors.Is(err, fs.ErrNotExist) {
		return nil, err
	}

	_, priv, err := ed25519.GenerateKey(rand.Reader)
	if err != nil {
		return nil, err
	}
	der, err := x509.MarshalPKCS8PrivateKey(priv)
	if err != nil {
		return nil, err
	}
	pemBytes := pem.EncodeToMemory(&pem.Block{Type: "PRIVATE KEY", Bytes: der})

	if err := os.MkdirAll(filepath.Dir(path), 0o700); err != nil {
		return nil, err
	}
	if err := os.WriteFile(path, pemBytes, 0o600); err != nil {
		return nil, err
	}
	return ssh.ParsePrivateKey(pemBytes)
}

func (s *Server) passwordCallback(c ssh.ConnMetadata, pw []byte) (*ssh.Permissions, error) {
	ip, _, err := net.SplitHostPort(c.RemoteAddr().String())
	if err != nil {
		ip = c.RemoteAddr().String()
	}

	ctx, cancel := context.WithTimeout(context.Background(), 10*time.Second)
	defer cancel()

	res, err := s.auth.Authenticate(ctx, c.User(), string(pw), ip)
	if err != nil {
		// username dan password sengaja nggak dicatat
		log.Printf("[sftp] login ditolak dari %s: %v", ip, err)
		return nil, errors.New("login ditolak")
	}

	return &ssh.Permissions{Extensions: map[string]string{
		"server":    res.Server,
		"read_only": strconv.FormatBool(res.ReadOnly),
	}}, nil
}

func (s *Server) ListenAndServe() error {
	l, err := net.Listen("tcp", s.cfg.Addr)
	if err != nil {
		return err
	}
	return s.Serve(l)
}

func (s *Server) Serve(l net.Listener) error {
	for {
		nc, err := l.Accept()
		if err != nil {
			if errors.Is(err, net.ErrClosed) {
				return nil
			}
			log.Printf("[sftp] accept gagal: %v", err)
			time.Sleep(100 * time.Millisecond)
			continue
		}

		select {
		case s.sem <- struct{}{}:
			go func() {
				defer func() { <-s.sem }()
				s.handleConn(nc)
			}()
		default:
			nc.Close() // kebanyakan koneksi
		}
	}
}

func (s *Server) handleConn(nc net.Conn) {
	defer nc.Close()

	_ = nc.SetDeadline(time.Now().Add(handshakeTimeout))
	sc, chans, reqs, err := ssh.NewServerConn(nc, s.ssh)
	if err != nil {
		return
	}
	_ = nc.SetDeadline(time.Time{})
	defer sc.Close()
	go ssh.DiscardRequests(reqs)

	uuid := sc.Permissions.Extensions["server"]
	readOnly := sc.Permissions.Extensions["read_only"] == "true"

	dir := filepath.Join(s.cfg.DataDir, uuid)
	if err := os.MkdirAll(dir, 0o755); err != nil {
		log.Printf("[sftp] gagal siapin folder server %s: %v", uuid, err)
		return
	}
	root, err := os.OpenRoot(dir)
	if err != nil {
		log.Printf("[sftp] gagal buka folder server %s: %v", uuid, err)
		return
	}
	defer root.Close()

	log.Printf("[sftp] login server=%s read_only=%v dari %s", uuid, readOnly, sc.RemoteAddr())

	h := &fsHandler{root: root, readOnly: readOnly}
	for nch := range chans {
		if nch.ChannelType() != "session" {
			_ = nch.Reject(ssh.UnknownChannelType, "hanya session")
			continue
		}
		ch, creqs, err := nch.Accept()
		if err != nil {
			continue
		}
		go serveSession(ch, creqs, h)
	}
}

func isSFTP(p []byte) bool {
	if len(p) < 4 {
		return false
	}
	return binary.BigEndian.Uint32(p[:4]) == uint32(len(p)-4) && string(p[4:]) == "sftp"
}

// serveSession cuma ngizinin subsystem "sftp". Shell, exec, pty, env semua ditolak.
func serveSession(ch ssh.Channel, reqs <-chan *ssh.Request, h *fsHandler) {
	defer ch.Close()

	started := false
	for req := range reqs {
		ok := !started && req.Type == "subsystem" && isSFTP(req.Payload)
		if req.WantReply {
			_ = req.Reply(ok, nil)
		}
		if ok {
			started = true
			go func() {
				rs := sftp.NewRequestServer(ch, sftp.Handlers{
					FileGet:  h,
					FilePut:  h,
					FileCmd:  h,
					FileList: h,
				})
				_ = rs.Serve()
				_ = rs.Close()
			}()
		}
	}
}
