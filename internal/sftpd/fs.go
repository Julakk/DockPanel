package sftpd

import (
	"io"
	"os"
	"path"
	"sort"
	"time"

	"github.com/pkg/sftp"
)

// fsHandler ngelayanin operasi SFTP di dalam satu os.Root (folder server).
// Semua path lewat os.Root, jadi ".." dan symlink yang keluar folder ditolak
// langsung oleh kernel/Go, bukan cuma dicek di sisi kita.
type fsHandler struct {
	root     *os.Root
	readOnly bool
}

// rel ngubah path SFTP (selalu absolut) jadi path relatif terhadap root. "/" jadi ".".
func rel(p string) string {
	c := path.Clean("/" + p)
	if c == "/" {
		return "."
	}
	return c[1:]
}

func (h *fsHandler) Fileread(r *sftp.Request) (io.ReaderAt, error) {
	f, err := h.root.Open(rel(r.Filepath))
	if err != nil {
		return nil, err
	}
	fi, err := f.Stat()
	if err != nil {
		f.Close()
		return nil, err
	}
	if fi.IsDir() {
		f.Close()
		return nil, sftp.ErrSSHFxFailure
	}
	return f, nil
}

func (h *fsHandler) Filewrite(r *sftp.Request) (io.WriterAt, error) {
	if h.readOnly {
		return nil, sftp.ErrSSHFxPermissionDenied
	}
	name := rel(r.Filepath)
	if name == "." {
		return nil, sftp.ErrSSHFxPermissionDenied
	}

	fl := r.Pflags()
	flag := os.O_WRONLY
	if fl.Creat {
		flag |= os.O_CREATE
	}
	if fl.Trunc {
		flag |= os.O_TRUNC
	}
	if fl.Excl {
		flag |= os.O_EXCL
	}

	f, err := h.root.OpenFile(name, flag, 0o644)
	if err != nil {
		return nil, err
	}
	return f, nil
}

func (h *fsHandler) Filecmd(r *sftp.Request) error {
	if h.readOnly {
		return sftp.ErrSSHFxPermissionDenied
	}
	name := rel(r.Filepath)

	switch r.Method {
	case "Setstat":
		return h.setstat(name, r)

	case "Rename":
		to := rel(r.Target)
		if name == "." || to == "." {
			return sftp.ErrSSHFxPermissionDenied
		}
		return h.root.Rename(name, to)

	case "Rmdir":
		if name == "." {
			return sftp.ErrSSHFxPermissionDenied
		}
		fi, err := h.root.Lstat(name)
		if err != nil {
			return err
		}
		if !fi.IsDir() {
			return sftp.ErrSSHFxFailure
		}
		return h.root.Remove(name) // gagal kalau folder nggak kosong

	case "Remove":
		if name == "." {
			return sftp.ErrSSHFxPermissionDenied
		}
		fi, err := h.root.Lstat(name)
		if err != nil {
			return err
		}
		if fi.IsDir() {
			return sftp.ErrSSHFxFailure
		}
		return h.root.Remove(name)

	case "Mkdir":
		if name == "." {
			return os.ErrExist
		}
		return h.root.Mkdir(name, 0o755)

	default: // Symlink, Link, dll: sengaja nggak didukung
		return sftp.ErrSSHFxOpUnsupported
	}
}

func (h *fsHandler) setstat(name string, r *sftp.Request) error {
	if name == "." {
		return nil
	}
	a := r.Attributes()
	if a == nil {
		return nil
	}
	fl := r.AttrFlags()

	if fl.Permissions {
		// dimask 0777: nggak boleh setuid/setgid/sticky
		if err := h.root.Chmod(name, os.FileMode(a.Mode)&0o777); err != nil {
			return err
		}
	}
	if fl.Acmodtime {
		if err := h.root.Chtimes(name, time.Unix(int64(a.Atime), 0), time.Unix(int64(a.Mtime), 0)); err != nil {
			return err
		}
	}
	if fl.Size {
		f, err := h.root.OpenFile(name, os.O_WRONLY, 0)
		if err != nil {
			return err
		}
		err = f.Truncate(int64(a.Size))
		if cerr := f.Close(); err == nil {
			err = cerr
		}
		if err != nil {
			return err
		}
	}
	// chown (UidGid) sengaja diabaikan
	return nil
}

type listerat []os.FileInfo

func (l listerat) ListAt(f []os.FileInfo, offset int64) (int, error) {
	if offset >= int64(len(l)) {
		return 0, io.EOF
	}
	n := copy(f, l[offset:])
	if n < len(f) {
		return n, io.EOF
	}
	return n, nil
}

func (h *fsHandler) Filelist(r *sftp.Request) (sftp.ListerAt, error) {
	name := rel(r.Filepath)

	switch r.Method {
	case "List":
		d, err := h.root.Open(name)
		if err != nil {
			return nil, err
		}
		defer d.Close()

		ents, err := d.ReadDir(-1)
		if err != nil {
			return nil, err
		}
		sort.Slice(ents, func(i, j int) bool { return ents[i].Name() < ents[j].Name() })

		out := make(listerat, 0, len(ents))
		for _, e := range ents {
			fi, err := e.Info()
			if err != nil {
				continue
			}
			out = append(out, fi)
		}
		return out, nil

	case "Stat":
		fi, err := h.root.Stat(name)
		if err != nil {
			return nil, err
		}
		return listerat{fi}, nil

	case "Lstat":
		fi, err := h.root.Lstat(name)
		if err != nil {
			return nil, err
		}
		return listerat{fi}, nil

	default: // Readlink dll
		return nil, sftp.ErrSSHFxOpUnsupported
	}
}
