import { UseFilters, UseGuards, UsePipes } from '@nestjs/common';
import { Args, Mutation } from '@nestjs/graphql';

import { AdminResolver } from 'src/engine/api/graphql/graphql-config/decorators/admin-resolver.decorator';
import { AuthGraphqlApiExceptionFilter } from 'src/engine/core-modules/auth/filters/auth-graphql-api-exception.filter';
import { EmailSenderService } from 'src/engine/core-modules/email/email-sender.service';
import { UserInputError } from 'src/engine/core-modules/graphql/utils/graphql-errors.util';
import { PreventNestToAutoLogGraphqlErrorsFilter } from 'src/engine/core-modules/graphql/filters/prevent-nest-to-auto-log-graphql-errors.filter';
import { ResolverValidationPipe } from 'src/engine/core-modules/graphql/pipes/resolver-validation.pipe';
import { TwentyConfigService } from 'src/engine/core-modules/twenty-config/twenty-config.service';
import { AccessPolicyGuard } from 'src/engine/guards/access-policy.guard';
import { AdminPanelGuard } from 'src/engine/guards/admin-panel-guard';

const EMAIL_ADDRESS_REGEX = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

// crmtwenty customization: lets the server admin check the company SMTP
// settings (entered in Admin Panel > Company email) by sending a real email.
// The email is sent synchronously so SMTP errors are reported to the admin.
@UsePipes(ResolverValidationPipe)
@AdminResolver()
@UseFilters(
  AuthGraphqlApiExceptionFilter,
  PreventNestToAutoLogGraphqlErrorsFilter,
)
@UseGuards(
  AccessPolicyGuard({
    authPrincipals: {
      userSession: {
        standard: true,
        impersonated: false,
        playground: false,
        workspaceAgnostic: false,
      },
      apiKey: false,
      oauthClient: false,
      application: false,
    },
    requiredPermissionFlags: [],
  }),
  AdminPanelGuard,
)
export class AdminPanelCompanyEmailResolver {
  constructor(
    private readonly emailSenderService: EmailSenderService,
    private readonly twentyConfigService: TwentyConfigService,
  ) {}

  @Mutation(() => Boolean)
  async sendAdminTestEmail(
    @Args('to', { type: () => String }) to: string,
  ): Promise<boolean> {
    const recipient = to.trim();

    if (!EMAIL_ADDRESS_REGEX.test(recipient)) {
      throw new UserInputError('آدرس ایمیل گیرنده معتبر نیست.');
    }

    const fromAddress = this.twentyConfigService.get('EMAIL_FROM_ADDRESS');
    const fromName = this.twentyConfigService.get('EMAIL_FROM_NAME');

    try {
      await this.emailSenderService.send({
        from: `"${fromName}" <${fromAddress}>`,
        to: recipient,
        subject: 'ایمیل آزمایشی CRM',
        text: 'این یک ایمیل آزمایشی است. تنظیمات ایمیل شرکت درست کار می‌کند.',
        html: '<div dir="rtl" style="font-family:Tahoma,sans-serif">این یک ایمیل آزمایشی است. تنظیمات ایمیل شرکت درست کار می‌کند.</div>',
      });
    } catch (error) {
      const message = error instanceof Error ? error.message : String(error);

      throw new UserInputError(`ارسال ایمیل ناموفق بود: ${message}`);
    }

    return true;
  }
}
