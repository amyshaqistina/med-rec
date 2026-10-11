<x-layouts::app :title="__('Register user')">
    <div class="mx-auto w-full max-w-xl px-6 py-8">
        <flux:heading size="xl" level="1">{{ __('Register user') }}</flux:heading>
        <flux:subheading size="lg" class="mb-6">{{ __('Create an account for a new staff member.') }}</flux:subheading>

        @if (session('status'))
            <flux:callout variant="success" icon="check-circle" class="mb-6">
                <flux:callout.text>{{ session('status') }}</flux:callout.text>
            </flux:callout>
        @endif

        <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-6">
            @csrf

            <flux:input
                name="name"
                :label="__('Full name')"
                :value="old('name')"
                type="text"
                required
                autofocus
                autocomplete="off"
            />

            <flux:input
                name="email"
                :label="__('Email address')"
                :value="old('email')"
                type="email"
                required
                autocomplete="off"
                placeholder="name@medrec.test"
            />

            <flux:input
                name="password"
                :label="__('Password')"
                type="password"
                required
                autocomplete="new-password"
                viewable
            />

            <flux:input
                name="password_confirmation"
                :label="__('Confirm password')"
                type="password"
                required
                autocomplete="new-password"
                viewable
            />

            <div class="flex items-center gap-3">
                <flux:button variant="primary" type="submit">{{ __('Register user') }}</flux:button>
                <flux:button :href="route('dashboard')" variant="ghost">{{ __('Cancel') }}</flux:button>
            </div>
        </form>
    </div>
</x-layouts::app>
