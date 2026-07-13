@extends('layouts.app')

@section('title', 'Settings - DockPanel')

@section('breadcrumb')
    Admin<span class="sep">&gt;</span>Settings
@endsection

@section('content')
    <h2>Panel Settings</h2>
    <p class="muted" style="margin-top:-0.6rem;">Configure DockPanel to your liking.</p>

    <div class="card">
        @if ($errors->any())
            <div class="error">
                @foreach ($errors->all() as $error)
                    {{ $error }}<br>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('settings.update') }}">
            @csrf
            @method('PUT')

            <label for="company_name">Company Name</label>
            <input type="text" name="company_name" id="company_name" value="{{ old('company_name', $setting->company_name) }}" required>
            <p class="muted" style="margin-top:-0.6rem;">Nama ini dipakai di seluruh panel.</p>

            <label for="require_2fa">Require Two-Factor Authentication</label>
            <select name="require_2fa" id="require_2fa">
                <option value="not_required" {{ old('require_2fa', $setting->require_2fa) == 'not_required' ? 'selected' : '' }}>Not Required</option>
                <option value="admin_only" {{ old('require_2fa', $setting->require_2fa) == 'admin_only' ? 'selected' : '' }}>Admin Only</option>
                <option value="all_users" {{ old('require_2fa', $setting->require_2fa) == 'all_users' ? 'selected' : '' }}>All Users</option>
            </select>

            <label for="default_language">Default Language</label>
            <select name="default_language" id="default_language">
                <option value="id" {{ old('default_language', $setting->default_language) == 'id' ? 'selected' : '' }}>Bahasa Indonesia</option>
                <option value="en" {{ old('default_language', $setting->default_language) == 'en' ? 'selected' : '' }}>English</option>
            </select>

            <button type="submit" class="btn btn-primary" style="margin-top:0.5rem;">Save</button>
        </form>
    </div>
@endsection
