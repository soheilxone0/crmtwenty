import { useMutation, useQuery } from '@apollo/client/react';
import { styled } from '@linaria/react';
import { useEffect, useMemo, useState } from 'react';
import { isDefined } from 'twenty-shared/utils';
import { useToast } from 'twenty-ui/components/feedback';
import { Section } from 'twenty-ui/components/layout';
import { Button } from 'twenty-ui/primitives/input';
import { Card } from 'twenty-ui/primitives/surfaces';
import { themeCssVariables } from 'twenty-ui/theme';

import { currentUserState } from '@/auth/states/currentUserState';
import { useClientConfig } from '@/client-config/hooks/useClientConfig';
import { useApolloAdminClient } from '@/settings/admin-panel/apollo/hooks/useApolloAdminClient';
import { SEND_ADMIN_TEST_EMAIL } from '@/settings/admin-panel/company-email/graphql/sendAdminTestEmail';
import { SettingsOptionCardContentSwitch } from '@/settings/components/SettingsOptions/SettingsOptionCardContentSwitch';
import { SettingsSectionSkeletonLoader } from '@/settings/components/SettingsSectionSkeletonLoader';
import { TextInput } from '@/ui/input/components/TextInput';
import { useAtomStateValue } from '@/ui/utilities/state/jotai/hooks/useAtomStateValue';
import {
  ConfigSource,
  CreateDatabaseConfigVariableDocument,
  GetConfigVariablesGroupedDocument,
  UpdateDatabaseConfigVariableDocument,
} from '~/generated-admin/graphql';

// crmtwenty customization: a Persian "Company email" page in the admin panel.
// The values are stored as database config variables, which take precedence
// over the server environment, so the SMTP account used for account
// activation and password reset emails can be changed without touching the
// server files.

const SMTP_PROVIDER_EMAIL_DRIVER = 'SMTP';

const TEXT_FIELDS = [
  {
    key: 'EMAIL_FROM_ADDRESS',
    label: 'ایمیل فرستنده',
    placeholder: 'info@company.ir',
    hint: 'نشانی‌ای که کاربران ایمیل‌ها را از آن دریافت می‌کنند.',
  },
  {
    key: 'EMAIL_FROM_NAME',
    label: 'نام فرستنده',
    placeholder: 'شرکت من',
    hint: 'نامی که در صندوق ورودی کاربر نمایش داده می‌شود.',
  },
  {
    key: 'EMAIL_SMTP_HOST',
    label: 'سرور SMTP',
    placeholder: 'mail.company.ir',
    hint: 'مثلاً smtp.gmail.com یا smtp.zoho.com یا سرور ایمیل هاست شما.',
  },
  {
    key: 'EMAIL_SMTP_PORT',
    label: 'پورت SMTP',
    placeholder: '587',
    hint: '۵۸۷ برای STARTTLS، ۴۶۵ برای SSL، ۲۵ بدون رمزنگاری.',
  },
  {
    key: 'EMAIL_SMTP_USER',
    label: 'نام کاربری SMTP',
    placeholder: 'info@company.ir',
    hint: 'معمولاً همان نشانی کامل ایمیل است.',
  },
  {
    key: 'EMAIL_SMTP_PASSWORD',
    label: 'رمز عبور SMTP',
    placeholder: '',
    hint: 'برای Gmail از «App Password» استفاده کنید.',
  },
] as const;

type TextFieldKey = (typeof TEXT_FIELDS)[number]['key'];

type FormState = Record<TextFieldKey, string> & {
  EMAIL_SMTP_NO_TLS: boolean;
  IS_EMAIL_VERIFICATION_REQUIRED: boolean;
  EMAIL_DRIVER_IS_SMTP: boolean;
};

const StyledContainer = styled.div`
  direction: rtl;
  display: flex;
  flex-direction: column;
  gap: ${themeCssVariables.spacing[6]};
  text-align: right;
`;

const StyledFields = styled.div`
  display: flex;
  flex-direction: column;
  gap: ${themeCssVariables.spacing[4]};
`;

const StyledActions = styled.div`
  display: flex;
  gap: ${themeCssVariables.spacing[2]};
  justify-content: flex-start;
`;

const StyledTestRow = styled.div`
  align-items: flex-end;
  display: flex;
  gap: ${themeCssVariables.spacing[2]};
`;

const StyledNote = styled.p`
  color: ${themeCssVariables.font.color.tertiary};
  font-size: ${themeCssVariables.font.size.sm};
  line-height: 1.8;
  margin: 0;
`;

const isEmpty = (value: string) => value.trim() === '';

export const SettingsAdminCompanyEmail = () => {
  const apolloAdminClient = useApolloAdminClient();
  const { refetch: refetchClientConfig } = useClientConfig();
  const { enqueueToast } = useToast();
  const currentUser = useAtomStateValue(currentUserState);

  const {
    data,
    loading,
    refetch: refetchConfigVariables,
  } = useQuery(GetConfigVariablesGroupedDocument, {
    client: apolloAdminClient,
    fetchPolicy: 'network-only',
  });

  const [createDatabaseConfigVariable] = useMutation(
    CreateDatabaseConfigVariableDocument,
    { client: apolloAdminClient },
  );
  const [updateDatabaseConfigVariable] = useMutation(
    UpdateDatabaseConfigVariableDocument,
    { client: apolloAdminClient },
  );
  const [sendAdminTestEmail] = useMutation<
    { sendAdminTestEmail: boolean },
    { to: string }
  >(SEND_ADMIN_TEST_EMAIL, { client: apolloAdminClient });

  const variablesByName = useMemo(() => {
    const variables =
      data?.getConfigVariablesGrouped.groups.flatMap(
        (group) => group.variables,
      ) ?? [];

    return new Map(variables.map((variable) => [variable.name, variable]));
  }, [data]);

  const [form, setForm] = useState<FormState | null>(null);
  const [isSaving, setIsSaving] = useState(false);
  const [isSendingTest, setIsSendingTest] = useState(false);
  const [testRecipient, setTestRecipient] = useState('');

  useEffect(() => {
    if (variablesByName.size === 0) {
      return;
    }

    const getStringValue = (key: string) => {
      const variable = variablesByName.get(key);

      // Sensitive values come back masked: keep the input empty and only
      // overwrite them when the admin types a new value.
      if (!isDefined(variable) || variable.isSensitive) {
        return '';
      }

      return isDefined(variable.value) ? String(variable.value) : '';
    };

    setForm({
      EMAIL_FROM_ADDRESS: getStringValue('EMAIL_FROM_ADDRESS'),
      EMAIL_FROM_NAME: getStringValue('EMAIL_FROM_NAME'),
      EMAIL_SMTP_HOST: getStringValue('EMAIL_SMTP_HOST'),
      EMAIL_SMTP_PORT: getStringValue('EMAIL_SMTP_PORT'),
      EMAIL_SMTP_USER: getStringValue('EMAIL_SMTP_USER'),
      EMAIL_SMTP_PASSWORD: '',
      EMAIL_SMTP_NO_TLS:
        variablesByName.get('EMAIL_SMTP_NO_TLS')?.value === true,
      IS_EMAIL_VERIFICATION_REQUIRED:
        variablesByName.get('IS_EMAIL_VERIFICATION_REQUIRED')?.value === true,
      EMAIL_DRIVER_IS_SMTP:
        variablesByName.get('EMAIL_DRIVER')?.value ===
        SMTP_PROVIDER_EMAIL_DRIVER,
    });
  }, [variablesByName]);

  useEffect(() => {
    if (isDefined(currentUser?.email) && isEmpty(testRecipient)) {
      setTestRecipient(currentUser.email);
    }
  }, [currentUser?.email, testRecipient]);

  if (loading || !isDefined(form)) {
    return <SettingsSectionSkeletonLoader />;
  }

  const getMaskedPlaceholder = (key: TextFieldKey, fallback: string) => {
    const variable = variablesByName.get(key);

    if (
      isDefined(variable) &&
      variable.isSensitive &&
      isDefined(variable.value) &&
      String(variable.value) !== ''
    ) {
      return `${String(variable.value)} (تنظیم شده — برای تغییر، مقدار جدید وارد کنید)`;
    }

    return fallback;
  };

  const saveVariable = async (key: string, value: string | number | boolean) => {
    const variable = variablesByName.get(key);

    if (variable?.isEnvOnly === true) {
      throw new Error(`${key} فقط از طریق فایل env قابل تغییر است.`);
    }

    if (variable?.source === ConfigSource.DATABASE) {
      await updateDatabaseConfigVariable({ variables: { key, value } });
    } else {
      await createDatabaseConfigVariable({ variables: { key, value } });
    }
  };

  const handleSave = async () => {
    if (form.EMAIL_DRIVER_IS_SMTP) {
      if (isEmpty(form.EMAIL_SMTP_HOST) || isEmpty(form.EMAIL_FROM_ADDRESS)) {
        enqueueToast({
          status: 'error',
          children: 'سرور SMTP و ایمیل فرستنده الزامی است.',
        });
        return;
      }
    }

    const port = Number(form.EMAIL_SMTP_PORT || '587');

    if (!Number.isInteger(port) || port <= 0 || port > 65535) {
      enqueueToast({ status: 'error', children: 'پورت SMTP معتبر نیست.' });
      return;
    }

    setIsSaving(true);

    try {
      for (const { key } of TEXT_FIELDS) {
        const value = form[key].trim();

        if (key === 'EMAIL_SMTP_PORT') {
          await saveVariable(key, port);
          continue;
        }

        // Empty sensitive inputs mean "keep the current value".
        if (isEmpty(value)) {
          continue;
        }

        await saveVariable(key, value);
      }

      await saveVariable('EMAIL_SMTP_NO_TLS', form.EMAIL_SMTP_NO_TLS);
      await saveVariable(
        'EMAIL_DRIVER',
        form.EMAIL_DRIVER_IS_SMTP ? SMTP_PROVIDER_EMAIL_DRIVER : 'LOGGER',
      );
      await saveVariable(
        'IS_EMAIL_VERIFICATION_REQUIRED',
        form.IS_EMAIL_VERIFICATION_REQUIRED,
      );

      await refetchConfigVariables();
      await refetchClientConfig();

      enqueueToast({
        status: 'success',
        children: 'تنظیمات ایمیل شرکت ذخیره شد.',
      });
    } catch (error) {
      enqueueToast({
        status: 'error',
        children: `ذخیره ناموفق بود: ${
          error instanceof Error ? error.message : String(error)
        }`,
      });
    } finally {
      setIsSaving(false);
    }
  };

  const handleSendTestEmail = async () => {
    setIsSendingTest(true);

    try {
      await sendAdminTestEmail({ variables: { to: testRecipient.trim() } });
      enqueueToast({
        status: 'success',
        children: `ایمیل آزمایشی به ${testRecipient.trim()} ارسال شد.`,
      });
    } catch (error) {
      enqueueToast({
        status: 'error',
        children:
          error instanceof Error ? error.message : 'ارسال ایمیل ناموفق بود.',
      });
    } finally {
      setIsSendingTest(false);
    }
  };

  const updateField = (key: keyof FormState, value: string | boolean) => {
    setForm((previous) =>
      isDefined(previous) ? { ...previous, [key]: value } : previous,
    );
  };

  return (
    <StyledContainer>
      <Section.Root>
        <Section.Header
          title="ایمیل شرکت (SMTP)"
          description="ایمیل فعال‌سازی حساب، فراموشی رمز عبور و دعوت‌نامه‌ها از این حساب ارسال می‌شوند."
        />
        <Card.Root>
          <SettingsOptionCardContentSwitch
            title="ارسال ایمیل از طریق SMTP"
            description="اگر خاموش باشد، ایمیل‌ها ارسال نمی‌شوند و فقط در لاگ سرور نوشته می‌شوند."
            checked={form.EMAIL_DRIVER_IS_SMTP}
            onChange={(checked) => updateField('EMAIL_DRIVER_IS_SMTP', checked)}
            divider
          />
          <SettingsOptionCardContentSwitch
            title="الزام تأیید ایمیل هنگام ثبت‌نام"
            description="کاربر جدید تا روی لینک فعال‌سازی ارسال‌شده کلیک نکند نمی‌تواند وارد شود."
            checked={form.IS_EMAIL_VERIFICATION_REQUIRED}
            onChange={(checked) =>
              updateField('IS_EMAIL_VERIFICATION_REQUIRED', checked)
            }
            divider
          />
          <SettingsOptionCardContentSwitch
            title="بدون TLS"
            description="فقط برای سرورهای داخلی که رمزنگاری ندارند روشن کنید."
            checked={form.EMAIL_SMTP_NO_TLS}
            onChange={(checked) => updateField('EMAIL_SMTP_NO_TLS', checked)}
          />
        </Card.Root>
      </Section.Root>

      <Section.Root>
        <Section.Header
          title="اطلاعات حساب ایمیل"
          description="این مقادیر در پایگاه داده ذخیره می‌شوند و بر تنظیمات فایل env سرور اولویت دارند."
        />
        <StyledFields>
          {TEXT_FIELDS.map((field) => (
            <TextInput
              key={field.key}
              label={field.label}
              hint={field.hint}
              placeholder={getMaskedPlaceholder(field.key, field.placeholder)}
              value={form[field.key]}
              onChange={(value) => updateField(field.key, value)}
              type={field.key === 'EMAIL_SMTP_PASSWORD' ? 'password' : 'text'}
              autoComplete={
                field.key === 'EMAIL_SMTP_PASSWORD' ? 'new-password' : 'off'
              }
              dir="ltr"
              fullWidth
            />
          ))}
        </StyledFields>
        <StyledActions>
          <Button
            variant="solid"
            color="accent"
            loading={isSaving}
            disabled={isSaving}
            onClick={() => void handleSave()}
          >
            ذخیره تنظیمات
          </Button>
        </StyledActions>
      </Section.Root>

      <Section.Root>
        <Section.Header
          title="ارسال ایمیل آزمایشی"
          description="پس از ذخیره، یک ایمیل آزمایشی بفرستید تا از درستی تنظیمات مطمئن شوید."
        />
        <StyledTestRow>
          <TextInput
            label="ایمیل گیرنده"
            value={testRecipient}
            onChange={setTestRecipient}
            dir="ltr"
            fullWidth
          />
          <Button
            variant="outline"
            loading={isSendingTest}
            disabled={isSendingTest || isEmpty(testRecipient)}
            onClick={() => void handleSendTestEmail()}
          >
            ارسال
          </Button>
        </StyledTestRow>
        <StyledNote>
          نکته: Gmail به «App Password» نیاز دارد (Google Account ← Security ←
          App passwords). برای ایمیل هاست‌های ایرانی معمولاً سرور mail.دامنه و
          پورت ۵۸۷ یا ۴۶۵ است.
        </StyledNote>
      </Section.Root>
    </StyledContainer>
  );
};
