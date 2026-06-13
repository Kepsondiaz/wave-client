<?php

namespace Alal\WaveClient\Actions;

use Alal\WaveClient\Data\TransactionData;
use Alal\WaveClient\Http\WaveConnector;

class Transactions
{
    private const HISTORY_QUERY = <<<'GQL'
        query HistoryEntries_BusinessWalletHistoryQuery(
          $start: Date!
          $end: Date!
          $walletOpaqueId: String!
          $limit: Int
          $transactionId: String
          $customerMobileStr: String
          $searchTerm: String
          $surrogateEmployeeId: String
          $includePending: Boolean
          $transactionType: TransactionType
        ) {
          me {
            businessUser {
              business {
                name
                walletHistory(start: $start, end: $end, walletOpaqueId: $walletOpaqueId, limit: $limit, transactionId: $transactionId, customerMobileStr: $customerMobileStr, surrogateEmployeeId: $surrogateEmployeeId, searchTerm: $searchTerm, includePending: $includePending, transactionType: $transactionType) {
                  historyEntries {
                    __typename
                    id
                    summary
                    whenEntered
                    amount
                    isPending
                    isCancelled
                    ... on MerchantSaleEntry {
                      isRefunded
                      isCheckout
                      clientReference
                      transferId
                      customerMobile: unmaskedSenderMobile
                      customerName: senderName
                      cashierName: merchantUName
                      grossAmount
                      feeAmount
                      actionSource
                    }
                    ... on PayoutTransferEntry {
                      tcid
                      maybeRecipientName: recipientName
                      recipientMobile
                      isReversal
                      isReversed
                      grossAmount
                    }
                    ... on TransferSentEntry {
                      isRefunded
                      recipientName
                      recipientMobile
                      transferOpaqueId: transferId
                    }
                    ... on TransferReceivedReversalEntry {
                      transferOpaqueId: transferId
                      senderName
                      senderMobile
                    }
                    ... on MerchantRefundEntry {
                      transferId
                      customerMobile: unmaskedSenderMobile
                      customerName: senderName
                      cashierName: merchantUName
                    }
                    ... on AgentTransactionEntry {
                      agentTransactionId
                      isDeposit
                      agentName
                      type
                    }
                    ... on BillPaymentEntry {
                      billName
                      billAccount
                      transferOpaqueId: transferId
                    }
                  }
                }
                id
              }
              id
            }
            id
          }
        }
        GQL;

    public function __construct(
        private readonly WaveConnector $connector,
    ) {}

    /**
     * Fetch wallet transaction history.
     *
     * @param  array{
     *   start?: string,
     *   end?: string,
     *   limit?: int,
     *   transactionId?: string,
     *   customerMobileStr?: string,
     *   searchTerm?: string,
     *   surrogateEmployeeId?: string,
     *   includePending?: bool,
     *   transactionType?: 'ALL'|'RECEIVED'|'SENT',
     * } $filters
     * @return TransactionData[]
     */
    public function list(array $filters = []): array
    {
        $walletId = $this->connector->session()->walletId();

        $data = $this->connector->graphql(self::HISTORY_QUERY, [
            'start'               => $filters['start'] ?? now()->startOfMonth()->toDateString(),
            'end'                 => $filters['end'] ?? now()->toDateString(),
            'walletOpaqueId'      => $walletId,
            'limit'               => $filters['limit'] ?? 100,
            'transactionId'       => $filters['transactionId'] ?? null,
            'customerMobileStr'   => $filters['customerMobileStr'] ?? null,
            'searchTerm'          => $filters['searchTerm'] ?? null,
            'surrogateEmployeeId' => $filters['surrogateEmployeeId'] ?? null,
            'includePending'      => $filters['includePending'] ?? true,
            'transactionType'     => $filters['transactionType'] ?? 'ALL',
        ]);

        $entries = $data['me']['businessUser']['business']['walletHistory']['historyEntries'] ?? [];

        return array_map(fn(array $entry) => TransactionData::from($entry), $entries);
    }

    /**
     * Find a specific transaction by its Wave ID.
     */
    public function find(string $transactionId): ?TransactionData
    {
        $results = $this->list([
            'transactionId'  => $transactionId,
            'includePending' => true,
        ]);

        return $results[0] ?? null;
    }
}
