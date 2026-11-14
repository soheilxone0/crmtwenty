import { gql } from '@apollo/client';

export const SEND_ADMIN_TEST_EMAIL = gql`
  mutation SendAdminTestEmail($to: String!) {
    sendAdminTestEmail(to: $to)
  }
`;
