Map<String, dynamic> walletSummaryFixture({
  int owner = 31,
  int viewer = 21,
  bool readOnly = false,
}) {
  final child = owner == 31;
  return {
    'id': child ? 7 : 6,
    'school_id': 1,
    'owner_user_id': owner,
    'owner_name': child
        ? 'Asha Synthetic'
        : owner == 21
        ? 'Farid Synthetic'
        : 'Staff Synthetic',
    'currency': 'ZAR',
    'balance_cents': child ? 5000 : 2000,
    'is_child': child,
    'read_only': readOnly,
    'can_manage_limits': !readOnly && (!child || viewer != owner),
    'school_daily_spend_cents': 5000,
    'child_limits_required': true,
    'limits': {
      'revision': child ? 1 : 0,
      'daily_spend_cents': child ? 1000 : null,
      'effective_daily_spend_cents': child ? 1000 : 5000,
      'all_categories': !child,
      'categories': child ? ['food'] : [],
      'configured': child,
    },
  };
}

Map<String, dynamic> walletIndexFixture({
  int viewer = 21,
  bool issued = true,
  bool readOnly = false,
}) => {
  'school_id': 1,
  'actor_user_id': viewer,
  'currency': 'ZAR',
  'owners': [
    for (final owner in viewer == 21 ? [21, 31] : [viewer])
      {
        'owner_user_id': owner,
        'owner_name': owner == 31
            ? 'Asha Synthetic'
            : owner == 21
            ? 'Farid Synthetic'
            : 'Staff Synthetic',
        'is_self': owner == viewer,
        'can_open': !readOnly,
        'wallets': issued
            ? [
                walletSummaryFixture(
                  owner: owner,
                  viewer: viewer,
                  readOnly: readOnly,
                ),
              ]
            : [],
      },
  ],
};
Map<String, dynamic> walletStatementFixture({
  int owner = 31,
  int viewer = 21,
  bool readOnly = false,
}) {
  final row = walletSummaryFixture(
    owner: owner,
    viewer: viewer,
    readOnly: readOnly,
  );
  return {
    ...row,
    'entries': {
      'items': [
        {
          'entry_id': 101,
          'posted_at': '2026-06-15T09:00:00.000000Z',
          'change_cents': row['balance_cents'],
          'balance_after_cents': row['balance_cents'],
          'description': 'Wallet credit',
        },
      ],
      'current_page': 1,
      'per_page': 30,
      'total': 1,
    },
  };
}

Map<String, dynamic> walletDeskReceiptsFixture({
  int owner = 31,
  int viewer = 21,
  bool readOnly = false,
}) => {
  'school_id': 1,
  'actor_user_id': viewer,
  'wallet_id': owner == 31 ? 7 : 6,
  'owner_user_id': owner,
  'currency': 'ZAR',
  'read_only': readOnly,
  'current_page': 1,
  'per_page': 30,
  'total': 3,
  'items': [
    for (final row in [
      (201, 'released', owner == 31 ? 5000 : 2000),
      (202, 'pending', 1000),
      (203, 'cancelled', 500),
    ])
      {
        'id': row.$1,
        'reference': 'WT-${row.$1}',
        'currency': 'ZAR',
        'amount_cents': row.$3,
        'status': row.$2,
        'created_at': '2026-06-15T09:00:00.000000Z',
        'processed_at': row.$2 == 'pending'
            ? null
            : '2026-06-15T09:01:00.000000Z',
        'collected_by': 'school',
        'fee_cents': 0,
        'net_cents': row.$3,
      },
  ],
};

Map<String, dynamic> walletTransferReceiptFixture({
  String operation = '00000000-0000-4000-8000-000000000301',
  int childOwner = 31,
  int cents = 1000,
}) => {
  'id': 301,
  'reference': 'WX-301',
  'school_id': 1,
  'actor_user_id': 21,
  'operation_id': operation,
  'from_wallet_id': 6,
  'to_wallet_id': 7,
  'from_owner_id': 21,
  'to_owner_id': childOwner,
  'kind': 'to_child',
  'currency': 'ZAR',
  'amount_cents': cents,
  'created_at': '2026-06-15T09:00:00.000000Z',
};

Map<String, dynamic> walletTransferPageFixture({
  int viewer = 21,
  bool? canTransfer,
  List<Map<String, dynamic>> items = const [],
}) => {
  'school_id': 1,
  'actor_user_id': viewer,
  'can_transfer': canTransfer ?? viewer == 21,
  'child_returns': 'current_approving_guardian',
  'policy_revision': 1,
  'current_page': 1,
  'per_page': 30,
  'total': items.length,
  'items': items,
};

Map<String, dynamic> walletDepositReceiptFixture({
  String operation = '00000000-0000-4000-8000-000000000401',
  int viewer = 21,
  String status = 'pending',
  int requestedCents = 2000,
  int verifiedCents = 2000,
  String method = 'eft',
  String reference = 'PARENT-DEPOSIT-001',
  String paidOn = '2026-06-15',
}) => {
  'id': 401,
  'reference': 'WD-401',
  'school_id': 1,
  'actor_user_id': viewer,
  'operation_id': operation,
  'wallet_id': 6,
  'currency': 'ZAR',
  'requested_cents': requestedCents,
  'requested_method': method,
  'requested_reference': reference,
  'paid_on': paidOn,
  'status': status,
  'reason_code': switch (status) {
    'verified' => 'verified',
    'cancelled' => 'withdrawn',
    'rejected' => 'not_verified',
    _ => null,
  },
  'created_at': '2026-06-15T09:00:00.000000Z',
  'processed_at': status == 'pending' ? null : '2026-06-15T09:01:00.000000Z',
  'verified_receipt': status == 'verified'
      ? {
          'reference': 'WP-501',
          'currency': 'ZAR',
          'amount_cents': verifiedCents,
          'method': method,
          'source_reference': 'BANK-VERIFIED-001',
          'received_on': paidOn,
          'collected_by': 'school',
          'fee_cents': 0,
          'net_cents': verifiedCents,
          'settlement_reference': method == 'eft' ? 'BANK-VERIFIED-001' : null,
        }
      : null,
};

Map<String, dynamic> walletDepositPageFixture({
  int viewer = 21,
  bool configured = false,
  bool readOnly = false,
  List<Map<String, dynamic>> items = const [],
}) => {
  'school_id': 1,
  'actor_user_id': viewer,
  'currency': 'ZAR',
  'is_parent': viewer == 21,
  'read_only': readOnly,
  'can_submit': viewer == 21 && configured && !readOnly,
  'cash_allowed': viewer == 21 && configured,
  'eft_allowed': viewer == 21 && configured,
  'current_page': 1,
  'per_page': 30,
  'total': items.length,
  'items': items,
};

Map<String, dynamic> walletWithdrawalReceiptFixture({
  String operation = '00000000-0000-4000-8000-000000000601',
  int viewer = 21,
  int wallet = 6,
  String method = 'eft',
  String status = 'requested',
  int cents = 30000,
}) => {
  'id': 601,
  'reference': 'WW-601',
  'school_id': 1,
  'holder_user_id': viewer,
  'operation_id': operation,
  'wallet_id': wallet,
  'currency': 'ZAR',
  'amount_cents': cents,
  'method': method,
  'status': status,
  'created_at': '2026-06-15T09:00:00.000000Z',
  'processed_at': status == 'requested' ? null : '2026-06-15T09:01:00.000000Z',
  'pickup_starts_at': method == 'cash' ? '2026-11-01T08:00:00.000000Z' : null,
  'pickup_ends_at': method == 'cash' ? '2026-11-01T09:00:00.000000Z' : null,
  'pickup_label': method == 'cash'
      ? '2026-11-01 10:00 – 2026-11-01 11:00 Africa/Johannesburg'
      : null,
  'paid_on': status == 'paid' ? '2026-06-15' : null,
  'payout_reference': status == 'paid' ? 'WW-PAY-801' : null,
};
Map<String, dynamic> walletWithdrawalPageFixture({
  int viewer = 21,
  bool configured = false,
  bool readOnly = false,
  List<Map<String, dynamic>> items = const [],
}) {
  final adult = ![11, 31].contains(viewer);
  return {
    'school_id': 1,
    'actor_user_id': viewer,
    'currency': 'ZAR',
    'is_adult': adult,
    'read_only': readOnly,
    'can_request': adult && configured && !readOnly,
    'policy_revision': configured ? 1 : null,
    'cash_threshold_cents': configured ? 25000 : null,
    'cash_at_boundary': configured ? false : null,
    'timezone': 'Africa/Johannesburg',
    'slots': adult && configured && !readOnly
        ? [
            {
              'id': 701,
              'starts_at': '2026-11-01T08:00:00.000000Z',
              'ends_at': '2026-11-01T09:00:00.000000Z',
              'label':
                  '2026-11-01 10:00 – 2026-11-01 11:00 Africa/Johannesburg',
              'available_places': 2,
            },
          ]
        : [],
    'current_page': 1,
    'per_page': 30,
    'total': items.length,
    'items': items,
  };
}
