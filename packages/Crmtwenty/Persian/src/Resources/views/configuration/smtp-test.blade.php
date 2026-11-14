{{--
    crmtwenty: "Send a test e-mail" on Configuration → Email → Company e-mail.

    The values currently typed in the SMTP form are sent along, so the account
    can be checked before it is saved.
--}}
<v-smtp-test>
    <div class="shimmer h-[42px] w-full rounded-md"></div>
</v-smtp-test>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-smtp-test-template"
    >
        <div class="grid gap-3">
            <x-admin::form.control-group class="!mb-0">
                <x-admin::form.control-group.label>
                    @lang('persian::app.configuration.smtp.test.to')
                </x-admin::form.control-group.label>

                <div class="flex gap-2 max-sm:flex-wrap">
                    <input
                        type="email"
                        dir="ltr"
                        class="w-full rounded border border-gray-300 px-2.5 py-2 text-sm font-normal text-gray-800 transition-all hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                        v-model="to"
                        placeholder="name@example.com"
                        @keydown.enter.prevent="send"
                    >

                    <button
                        type="button"
                        class="secondary-button whitespace-nowrap"
                        :disabled="isSending || ! to"
                        @click="send"
                    >
                        <span v-if="isSending">@lang('persian::app.configuration.smtp.test.sending')</span>

                        <span v-else>@lang('persian::app.configuration.smtp.test.button')</span>
                    </button>
                </div>
            </x-admin::form.control-group>

            <p
                v-if="result"
                class="rounded px-3 py-2 text-sm leading-6"
                :class="result.ok
                    ? 'bg-green-50 text-green-700 dark:bg-green-950 dark:text-green-300'
                    : 'bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-300'"
                v-text="result.message"
                style="overflow-wrap: anywhere;"
            ></p>

            <p class="text-xs leading-5 text-gray-500 dark:text-gray-400">
                @lang('persian::app.configuration.smtp.test.hint')
            </p>
        </div>
    </script>

    <script type="module">
        app.component('v-smtp-test', {
            template: '#v-smtp-test-template',

            data() {
                return {
                    to: @json(auth()->guard('user')->user()?->email),

                    isSending: false,

                    result: null,
                };
            },

            methods: {
                /**
                 * Current (unsaved) value of an SMTP field on this page.
                 */
                field(name) {
                    const selector = `[name="email[smtp][account][${name}]"]`;

                    // Boolean fields render a hidden "0" before the checkbox itself.
                    const element = document.querySelector(`input[type="checkbox"]${selector}`)
                        || document.querySelector(selector);

                    if (! element) {
                        return null;
                    }

                    if (element.type === 'checkbox') {
                        return element.checked ? 1 : 0;
                    }

                    return element.value === '' ? null : element.value;
                },

                send() {
                    if (! this.to || this.isSending) {
                        return;
                    }

                    this.isSending = true;

                    this.result = null;

                    const payload = { to: this.to };

                    ['host', 'port', 'encryption', 'username', 'password', 'verify_peer', 'from_address', 'from_name']
                        .forEach((name) => {
                            const value = this.field(name);

                            if (value !== null) {
                                payload[name] = value;
                            }
                        });

                    this.$axios.post("{{ route('admin.configuration.smtp.test') }}", payload)
                        .then((response) => {
                            this.result = { ok: true, message: response.data.message };
                        })
                        .catch((error) => {
                            this.result = {
                                ok: false,
                                message: error.response?.data?.message || "@lang('persian::app.configuration.smtp.test.unknown-error')",
                            };
                        })
                        .finally(() => this.isSending = false);
                },
            },
        });
    </script>
@endPushOnce
