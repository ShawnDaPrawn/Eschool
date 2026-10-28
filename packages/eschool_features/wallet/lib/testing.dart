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
