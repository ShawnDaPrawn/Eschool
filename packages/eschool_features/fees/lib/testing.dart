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
        <String, dynamic>{
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

/// This holder's private share; original VAT130, reversed39, current VAT91.
Map<String, dynamic> feeVatStatementFixture({
  int schoolId = 1,
  int holderId = 21,
}) {
  final data = feeCreditedStatementFixture(
    schoolId: schoolId,
    holderId: holderId,
  );
  final rows = ((data['charges'] as Map)['items'] as List)
      .cast<Map<String, dynamic>>();
  for (var i = 0; i < rows.length; i++) {
    rows[i].addAll({
      'description': 'Synthetic taxed family item',
      'charge_kind': 'fee_item',
      'charge_scope': 'family',
      'original_vat_cents': 65,
      'vat_credited_cents': i == 0 ? 39 : 0,
      'net_charged_cents': i == 0 ? 175 : 436,
    });
  }
  (((data['credit_notes'] as Map)['items'] as List).single
          as Map<String, dynamic>)
      .addAll({'vat_cents': 39, 'net_cents': 261});
  return data;
}

/// Current-year offers contain only this payer's share and approval state.
Map<String, dynamic> feeOptionalItemsFixture({
  int schoolId = 1,
  int holderId = 21,
  int familyId = 7,
  bool writable = true,
}) => <String, dynamic>{
  'school_id': schoolId,
  'holder_user_id': holderId,
  'fee_account_id': familyId,
  'session_year_id': 3,
  'writable': writable,
  'current_page': 1,
  'per_page': 20,
  'total': 2,
  'items': <Map<String, dynamic>>[
    <String, dynamic>{
      'fee_item_id': 91,
      'name': 'Synthetic optional family excursion',
      'scope': 'family',
      'billing': 'once_per_year',
      'learner_id': null,
      'learner_name': null,
      'currency': 'ZAR',
      'available': true,
      'can_approve': true,
      'price_cents': 1001,
      'share_cents': 501,
      'vat_cents': 66,
      'selected': false,
      'current_terms_approved': false,
      'revision': 0,
      'fingerprint': 'a' * 64,
    },
    <String, dynamic>{
      'fee_item_id': 92,
      'name': 'Synthetic recurring learner activity',
      'scope': 'learner',
      'billing': 'per_occurrence',
      'learner_id': 11,
      'learner_name': 'Asha Synthetic',
      'currency': 'ZAR',
      'available': true,
      'can_approve': true,
      'price_cents': 3000,
      'share_cents': 1500,
      'vat_cents': 0,
      'selected': true,
      'current_terms_approved': true,
      'revision': 1,
      'fingerprint': 'b' * 64,
    },
  ],
};

/// Original receipt credit reserved and partly refunded, without changing charges.
Map<String, dynamic> feeRefundedStatementFixture() {
  final data = feeVatStatementFixture();
  final row =
      ((data['receipts'] as Map<String, dynamic>)['items'] as List).first
          as Map<String, dynamic>;
  row['pending_refund_cents'] = 20;
  row['refunded_cents'] = 10;
  row['credit_cents'] = (row['credit_cents'] as int) - 30;
  data['credit_cents'] = (data['credit_cents'] as int) - 30;
  data['net_cents'] = (data['net_cents'] as int) + 30;
  return data;
}

Map<String, dynamic> feeRefundedAccountsFixture() {
  final data = feeCreditedAccountsFixture();
  final row = (data['items'] as List).first as Map<String, dynamic>;
  row['credit_cents'] = (row['credit_cents'] as int) - 30;
  row['net_cents'] = (row['net_cents'] as int) + 30;
  return data;
}
