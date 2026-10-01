package sftpd

import (
	"bytes"
	"context"
	"encoding/json"
	"errors"
	"fmt"
	"io"
	"net/http"
	"regexp"
	"strings"
	"time"
)

var serverUUIDRe = regexp.MustCompile(`^[a-zA-Z0-9][a-zA-Z0-9_-]{0,63}$`)

// AuthResult balasan Panel kalau login valid.
type AuthResult struct {
	Server   string `json:"server"`
	ReadOnly bool   `json:"read_only"`
}

// Authenticator memverifikasi login SFTP. Di produksi = Panel (PanelAuth).
type Authenticator interface {
	Authenticate(ctx context.Context, username, password, ip string) (AuthResult, error)
}

// PanelAuth nanya ke POST {panel}/api/remote/sftp/auth pakai Bearer daemon_token.
type PanelAuth struct {
	BaseURL string
	Token   string
	Client  *http.Client
}

func NewPanelAuth(baseURL, token string) *PanelAuth {
	return &PanelAuth{
		BaseURL: strings.TrimRight(baseURL, "/"),
		Token:   token,
		Client: &http.Client{
			Timeout: 10 * time.Second,
			// Jangan ikut redirect: POST yang di-redirect berubah jadi GET dan gagal diam-diam.
			CheckRedirect: func(*http.Request, []*http.Request) error { return http.ErrUseLastResponse },
		},
	}
}

func (p *PanelAuth) Authenticate(ctx context.Context, username, password, ip string) (AuthResult, error) {
	var res AuthResult

	body, err := json.Marshal(map[string]string{"username": username, "password": password, "ip": ip})
	if err != nil {
		return res, err
	}

	req, err := http.NewRequestWithContext(ctx, http.MethodPost, p.BaseURL+"/api/remote/sftp/auth", bytes.NewReader(body))
	if err != nil {
		return res, err
	}
	req.Header.Set("Authorization", "Bearer "+p.Token)
	req.Header.Set("Accept", "application/json")
	req.Header.Set("Content-Type", "application/json")

	resp, err := p.Client.Do(req)
	if err != nil {
		return res, fmt.Errorf("panel nggak bisa dihubungi: %w", err)
	}
	defer resp.Body.Close()

	if resp.StatusCode != http.StatusOK {
		_, _ = io.Copy(io.Discard, io.LimitReader(resp.Body, 1<<16))
		return res, fmt.Errorf("panel menolak login (HTTP %d)", resp.StatusCode)
	}

	if err := json.NewDecoder(io.LimitReader(resp.Body, 1<<16)).Decode(&res); err != nil {
		return AuthResult{}, fmt.Errorf("balasan panel nggak valid: %w", err)
	}
	if !serverUUIDRe.MatchString(res.Server) {
		return AuthResult{}, errors.New("panel ngasih uuid server yang nggak valid")
	}

	return res, nil
}
