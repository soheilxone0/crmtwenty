@if (session('success'))
    <div class="alert alert-success"><i class="pi pi-check-circle"></i><div>{{ session('success') }}</div></div>
@endif

@if (session('error'))
    <div class="alert alert-error"><i class="pi pi-warning"></i><div>{{ session('error') }}</div></div>
@endif
