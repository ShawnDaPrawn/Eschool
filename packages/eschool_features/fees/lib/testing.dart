/// Synthetic statement fixtures shared by browser/native and package checks.
Map<String, dynamic> feeAccountFixture({int schoolId = 1, int holderId = 21}) =>
    {
      'id': 7,
      'school_id': schoolId,
      'holder_user_id': holderId,
      'name': 'Synthetic family',
      'reference': 'NEF-SYNTHETIC',
      'currency': 'ZAR',
      'charged_cents': 1002,
      'allocated_cents': 900,
      'outstanding_cents': 102,
      'credit_cents': 50,
      'net_cents': 52,
      'next_due_on': '2026-07-01',
      'scope': 'your_visible_ledger_records',
    };

Map<String, dynamic> feeAccountsFixture({
  int schoolId = 1,
  int holderId = 21,
}) => {
  'school_id': schoolId,
  'holder_user_id': holderId,
  'items': [feeAccountFixture(schoolId: schoolId, holderId: holderId)],
  'current_page': 1,
  'per_page': 20,
  'total': 1,
};

Map<String, dynamic> feeStatementFixture({
  int schoolId = 1,
  int holderId = 21,
}) => {
  ...feeAccountFixture(schoolId: schoolId, holderId: holderId),
  'charges': {
    'items': [
      for (final child in [11, 12])
        <String, dynamic>{
          'invoice_line_id': child,
          'invoice_number': 'BILL-SYNTHETIC',
          'learner_id': child,
          'learner_name': child == 11 ? 'Asha Synthetic' : 'Cara Synthetic',
          'description': 'Annual tuition',
          'period_start': '2026-01-01',
          'period_end': '2026-12-31',
          'issued_on': '2026-06-15',
          'due_on': '2026-07-01',
          'currency': 'ZAR',
          'charged_cents': 501,
          'allocated_cents': child == 11 ? 501 : 399,
          'outstanding_cents': child == 11 ? 0 : 102,
        },
    ],
    'current_page': 1,
    'per_page': 20,
    'total': 2,
  },
  'receipts': {
    'items': [
      for (final receipt in [1, 2])
        <String, dynamic>{
          'id': receipt,
          'number': 'FEE-PAY-SYNTHETIC-$receipt',
          'pinned_learner_id': receipt == 2 ? 12 : null,
          'method': receipt == 1 ? 'eft' : 'cash',
          'received_on': '2026-06-15',
          'currency': 'ZAR',
          'amount_cents': receipt == 1 ? 900 : 50,
          'allocated_cents': receipt == 1 ? 900 : 0,
          'credit_cents': receipt == 1 ? 0 : 50,
        },
    ],
    'current_page': 1,
    'per_page': 20,
    'total': 2,
  },
};

/// A paid share correction returns 300 cents to its original receipt.
Map<String, dynamic> feeCreditedStatementFixture({
  int schoolId = 1,
  int holderId = 21,
}) {
  final original = feeStatementFixture(schoolId: schoolId, holderId: holderId);
  final charges = (original['charges'] as Map<String, dynamic>);
  final rows = (charges['items'] as List).cast<Map<String, dynamic>>();
  rows[0].addAll({
    'gross_charged_cents': 501,
    'credited_cents': 300,
    'charged_cents': 201,
    'allocated_cents': 201,
  });
  rows[1].addAll({'gross_charged_cents': 501, 'credited_cents': 0});
  final receiptRows =
      ((original['receipts'] as Map<String, dynamic>)['items'] as List)
          .cast<Map<String, dynamic>>();
  receiptRows[0].addAll({
    'allocated_cents': 600,
    'credit_cents': 300,
    'released_cents': 300,
  });
  return {
    ...original,
    'gross_charged_cents': 1002,
    'credited_cents': 300,
    'charged_cents': 702,
    'allocated_cents': 600,
    'credit_cents': 350,
    'net_cents': -248,
    'credit_notes': {
      'items': [
        {
          'id': 31,
          'number': 'FEE-CN-SYNTHETIC-1',
          'invoice_line_id': 11,
          'invoice_number': 'BILL-SYNTHETIC',
          'learner_id': 11,
          'learner_name': 'Asha Synthetic',
          'currency': 'ZAR',
          'amount_cents': 300,
          'unpaid_cents': 0,
          'released_cents': 300,
          'reason_code': 'billing_error',
          'issued_on': '2026-06-15',
        },
      ],
      'current_page': 1,
      'per_page': 20,
      'total': 1,
    },
  };
}

Map<String, dynamic> feeCreditedAccountsFixture({
  int schoolId = 1,
  int holderId = 21,
}) => {
  ...feeAccountsFixture(schoolId: schoolId, holderId: holderId),
  'items': [
    {
      ...feeAccountFixture(schoolId: schoolId, holderId: holderId),
      'gross_charged_cents': 1002,
      'credited_cents': 300,
      'charged_cents': 702,
      'allocated_cents': 600,
      'credit_cents': 350,
      'net_cents': -248,
    },
  ],
};

/// One family item is apportioned over real learners, retaining credit history.
Map<String, dynamic> feeItemStatementFixture({
  int schoolId = 1,
  int holderId = 21,
}) {
  final data = feeCreditedStatementFixture(
    schoolId: schoolId,
    holderId: holderId,
  );
  for (final row in ((data['charges'] as Map)['items'] as List)) {
    (row as Map<String, dynamic>).addAll({
      'charge_kind': 'fee_item',
      'charge_scope': 'family',
      'description': 'Synthetic family activity fee',
      'invoice_number': 'ITEM-SYNTHETIC',
    });
  }
  (((data['credit_notes'] as Map)['items'] as List).single
          as Map<String, dynamic>)['invoice_number'] =
      'ITEM-SYNTHETIC';
  return data;
}
