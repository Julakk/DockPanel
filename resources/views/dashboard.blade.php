@extends('layouts.app')

@section('title', 'Overview')

@section('breadcrumb')
    Admin<span class="sep">&gt;</span>Index
@endsection

@section('content')
    <h2 class="nd-title">Administrative Overview <small>A quick glance at your system.</small></h2>

    <div class="nd-box nd-ok">
        <div class="nd-box-head"><h3>System Information</h3></div>
        <div class="nd-box-body">
            You are running DockPanel version <span class="nd-code">{{ config('app.version') }}</span>.
        </div>
    </div>

    <div class="nd-ovbtns">
        <a href="https://github.com/Julakk/DockPanel/issues" target="_blank" rel="noopener" class="nd-btn nd-btn-orange">Report Issue</a>
        <a href="https://github.com/Julakk/DockPanel/blob/main/CHANGELOG.md" target="_blank" rel="noopener" class="nd-btn nd-btn-blue">Changelog</a>
        <a href="https://github.com/Julakk/DockPanel" target="_blank" rel="noopener" class="nd-btn nd-btn-blue">GitHub</a>
        <a href="https://github.com/Julakk/DockWings" target="_blank" rel="noopener" class="nd-btn nd-btn-green">DockWings</a>
    </div>
@endsection
