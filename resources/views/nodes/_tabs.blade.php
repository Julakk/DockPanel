<div class="nd-tabs">
    <a href="{{ route('nodes.show', $node) }}" class="{{ $active === 'about' ? 'active' : '' }}">About</a>
    <a href="{{ route('nodes.edit', $node) }}" class="{{ $active === 'settings' ? 'active' : '' }}">Settings</a>
    <a href="{{ route('nodes.config', $node) }}" class="{{ $active === 'config' ? 'active' : '' }}">Configuration</a>
    <a href="{{ route('nodes.show', $node) }}#allocation">Allocation</a>
</div>
