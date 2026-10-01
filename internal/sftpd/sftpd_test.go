package sftpd

import (
	"bytes"
	"context"
	"encoding/json"
	"errors"
	"io"
	"net"
	"net/http"
	"net/http/httptest"
	"os"
	"path/filepath"
	"testing"
	"time"

	"github.com/pkg/sftp"
	"golang.org/x/crypto/ssh"
)

type fakeAuth struct{}

func (fakeAuth) Authenticate(_ context.Context, user, pass, _ string) (AuthResult, error) {
	switch {
	case user == "rw" && pass == "pw":
		return AuthResult{Server: "srv1"}, nil
	case user == "ro" && pass == "pw":
		return AuthResult{Server: "srv1", ReadOnly: true}, nil
	}
	return AuthResult{}, errors.New("denied")
}

func startServer(t *testing.T) (addr, dataDir string) {
	t.Helper()
	dataDir = t.TempDir()
	if err := os.MkdirAll(filepath.Join(dataDir, "srv1"), 0o755); err != nil {
		t.Fatal(err)
	}
	s, err := New(Config{HostKeyPath: filepath.Join(t.TempDir(), "host_key"), DataDir: dataDir}, fakeAuth{})
	if err != nil {
		t.Fatal(err)
	}
	l, err := net.Listen("tcp", "127.0.0.1:0")
	if err != nil {
		t.Fatal(err)
	}
	t.Cleanup(func() { l.Close() })
	go s.Serve(l)
	return l.Addr().String(), dataDir
}

func dial(t *testing.T, addr, user, pass string) (*sftp.Client, error) {
	t.Helper()
	conn, err := ssh.Dial("tcp", addr, &ssh.ClientConfig{
		User:            user,
		Auth:            []ssh.AuthMethod{ssh.Password(pass)},
		HostKeyCallback: ssh.InsecureIgnoreHostKey(),
		Timeout:         5 * time.Second,
	})
	if err != nil {
		return nil, err
	}
	t.Cleanup(func() { conn.Close() })
	c, err := sftp.NewClient(conn)
	if err != nil {
		return nil, err
	}
	t.Cleanup(func() { c.Close() })
	return c, nil
}

func TestHostKeyPersistent(t *testing.T) {
	p := filepath.Join(t.TempDir(), "k")
	a, err := loadOrCreateHostKey(p)
	if err != nil {
		t.Fatal(err)
	}
	b, err := loadOrCreateHostKey(p)
	if err != nil {
		t.Fatal(err)
	}
	if !bytes.Equal(a.PublicKey().Marshal(), b.PublicKey().Marshal()) {
		t.Error("host key berubah antar load")
	}
}

func TestSFTPLoginDitolak(t *testing.T) {
	addr, _ := startServer(t)
	if c, err := dial(t, addr, "rw", "salah"); err == nil {
		c.Close()
		t.Fatal("login dengan password salah harusnya gagal")
	}
}

func TestSFTPFileOps(t *testing.T) {
	addr, dataDir := startServer(t)
	srv := filepath.Join(dataDir, "srv1")

	c, err := dial(t, addr, "rw", "pw")
	if err != nil {
		t.Fatalf("dial: %v", err)
	}

	f, err := c.Create("/hello.txt")
	if err != nil {
		t.Fatalf("Create: %v", err)
	}
	if _, err := f.Write([]byte("halo")); err != nil {
		t.Fatal(err)
	}
	if err := f.Close(); err != nil {
		t.Fatal(err)
	}

	got, err := os.ReadFile(filepath.Join(srv, "hello.txt"))
	if err != nil || string(got) != "halo" {
		t.Fatalf("isi file di disk = %q err=%v", got, err)
	}

	rf, err := c.Open("/hello.txt")
	if err != nil {
		t.Fatalf("Open: %v", err)
	}
	data, err := io.ReadAll(rf)
	rf.Close()
	if err != nil || string(data) != "halo" {
		t.Fatalf("baca = %q err=%v", data, err)
	}

	if err := c.Mkdir("/plugins"); err != nil {
		t.Fatalf("Mkdir: %v", err)
	}
	if err := c.Rename("/hello.txt", "/plugins/hi.txt"); err != nil {
		t.Fatalf("Rename: %v", err)
	}

	infos, err := c.ReadDir("/plugins")
	if err != nil || len(infos) != 1 || infos[0].Name() != "hi.txt" {
		t.Fatalf("ReadDir = %v err=%v", infos, err)
	}

	if err := c.Remove("/plugins/hi.txt"); err != nil {
		t.Fatalf("Remove: %v", err)
	}
	if err := c.RemoveDirectory("/plugins"); err != nil {
		t.Fatalf("RemoveDirectory: %v", err)
	}
	if _, err := os.Stat(filepath.Join(srv, "plugins")); err == nil {
		t.Error("folder masih ada setelah dihapus")
	}
}

func TestSFTPJail(t *testing.T) {
	addr, dataDir := startServer(t)
	srv := filepath.Join(dataDir, "srv1")

	// target di luar folder server
	if err := os.WriteFile(filepath.Join(dataDir, "secret.txt"), []byte("rahasia"), 0o644); err != nil {
		t.Fatal(err)
	}
	outside := filepath.Join(dataDir, "outside")
	if err := os.MkdirAll(outside, 0o755); err != nil {
		t.Fatal(err)
	}
	if err := os.WriteFile(filepath.Join(outside, "secret.txt"), []byte("rahasia"), 0o644); err != nil {
		t.Fatal(err)
	}
	if err := os.Symlink(outside, filepath.Join(srv, "link")); err != nil {
		t.Skipf("symlink nggak didukung di sini: %v", err)
	}

	c, err := dial(t, addr, "rw", "pw")
	if err != nil {
		t.Fatalf("dial: %v", err)
	}

	// ".." dinetralkan: "/../secret.txt" = <folder server>/secret.txt, yang nggak ada
	if f, err := c.Open("/../secret.txt"); err == nil {
		f.Close()
		t.Error("bisa baca file di luar folder server lewat ..")
	}

	// nulis lewat ".." jatuh DI DALAM folder server
	f, err := c.Create("/../escape.txt")
	if err != nil {
		t.Fatalf("Create: %v", err)
	}
	f.Close()
	if _, err := os.Stat(filepath.Join(dataDir, "escape.txt")); err == nil {
		t.Error("file kebuat di luar folder server")
	}
	if _, err := os.Stat(filepath.Join(srv, "escape.txt")); err != nil {
		t.Errorf("file harusnya kebuat di dalam folder server: %v", err)
	}

	// symlink yang nunjuk keluar folder nggak boleh diikuti
	if _, err := c.ReadDir("/link"); err == nil {
		t.Error("bisa list folder lewat symlink keluar")
	}
	if f, err := c.Open("/link/secret.txt"); err == nil {
		f.Close()
		t.Error("bisa baca file lewat symlink keluar")
	}
	if f, err := c.Create("/link/baru.txt"); err == nil {
		f.Close()
		t.Error("bisa nulis lewat symlink keluar")
	}
	if _, err := os.Stat(filepath.Join(outside, "baru.txt")); err == nil {
		t.Error("file kebuat di luar folder server lewat symlink")
	}

	// bikin symlink lewat SFTP nggak didukung
	if err := c.Symlink("/etc", "/x"); err == nil {
		t.Error("symlink harusnya ditolak")
	}
}

func TestSFTPReadOnly(t *testing.T) {
	addr, dataDir := startServer(t)
	if err := os.WriteFile(filepath.Join(dataDir, "srv1", "a.txt"), []byte("x"), 0o644); err != nil {
		t.Fatal(err)
	}

	c, err := dial(t, addr, "ro", "pw")
	if err != nil {
		t.Fatalf("dial: %v", err)
	}

	rf, err := c.Open("/a.txt")
	if err != nil {
		t.Fatalf("baca harusnya boleh: %v", err)
	}
	data, _ := io.ReadAll(rf)
	rf.Close()
	if string(data) != "x" {
		t.Errorf("isi = %q", data)
	}

	if f, err := c.Create("/b.txt"); err == nil {
		f.Close()
		t.Error("Create harusnya ditolak buat read-only")
	}
	if err := c.Remove("/a.txt"); err == nil {
		t.Error("Remove harusnya ditolak buat read-only")
	}
	if err := c.Mkdir("/d"); err == nil {
		t.Error("Mkdir harusnya ditolak buat read-only")
	}
	if _, err := os.Stat(filepath.Join(dataDir, "srv1", "a.txt")); err != nil {
		t.Errorf("file kehapus padahal read-only: %v", err)
	}
}

func TestPanelAuth(t *testing.T) {
	ts := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		if r.URL.Path != "/api/remote/sftp/auth" || r.Method != http.MethodPost {
			http.NotFound(w, r)
			return
		}
		if r.Header.Get("Authorization") != "Bearer tok" {
			http.Error(w, `{"error":"x"}`, http.StatusUnauthorized)
			return
		}
		var in map[string]string
		_ = json.NewDecoder(r.Body).Decode(&in)
		switch in["username"] {
		case "ok":
			w.Write([]byte(`{"server":"abc-1","read_only":true}`))
		case "evil":
			w.Write([]byte(`{"server":"../../etc","read_only":false}`))
		default:
			http.Error(w, `{"error":"no"}`, http.StatusUnauthorized)
		}
	}))
	defer ts.Close()

	ctx := context.Background()
	p := NewPanelAuth(ts.URL+"/", "tok")

	res, err := p.Authenticate(ctx, "ok", "pw", "1.2.3.4")
	if err != nil || res.Server != "abc-1" || !res.ReadOnly {
		t.Fatalf("res=%+v err=%v", res, err)
	}
	if _, err := p.Authenticate(ctx, "evil", "pw", "1.2.3.4"); err == nil {
		t.Error("uuid server berbahaya harusnya ditolak")
	}
	if _, err := p.Authenticate(ctx, "nope", "pw", "1.2.3.4"); err == nil {
		t.Error("kredensial salah harusnya ditolak")
	}
	if _, err := NewPanelAuth(ts.URL, "salah").Authenticate(ctx, "ok", "pw", "1.2.3.4"); err == nil {
		t.Error("token node salah harusnya ditolak")
	}
}
