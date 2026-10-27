//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/parent_fee_credit_page.dart';
import 'package:eschool_api/src/model/parent_fee_receipt_page.dart';
import 'package:eschool_api/src/model/parent_fee_charge_page.dart';
import 'package:json_annotation/json_annotation.dart';

part 'parent_fee_statement.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ParentFeeStatement {
  /// Returns a new [ParentFeeStatement] instance.
  ParentFeeStatement({
    required this.id,

    required this.schoolId,

    required this.holderUserId,

    required this.name,

    required this.reference,

    required this.currency,

    required this.chargedCents,

    required this.allocatedCents,

    required this.outstandingCents,

    required this.creditCents,

    required this.netCents,

    required this.nextDueOn,

    required this.scope,

    required this.charges,

    required this.receipts,

    this.grossChargedCents,

    this.creditedCents,

    this.creditNotes,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  // minimum: 1
  @JsonKey(name: r'holder_user_id', required: true, includeIfNull: false)
  final int holderUserId;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'reference', required: true, includeIfNull: false)
  final String reference;

  @JsonKey(name: r'currency', required: true, includeIfNull: false)
  final String currency;

  /// Current net invoice share after posted credit notes; equals allocated plus outstanding cents.
  // minimum: 0
  @JsonKey(name: r'charged_cents', required: true, includeIfNull: false)
  final int chargedCents;

  // minimum: 0
  @JsonKey(name: r'allocated_cents', required: true, includeIfNull: false)
  final int allocatedCents;

  // minimum: 0
  @JsonKey(name: r'outstanding_cents', required: true, includeIfNull: false)
  final int outstandingCents;

  // minimum: 0
  @JsonKey(name: r'credit_cents', required: true, includeIfNull: false)
  final int creditCents;

  @JsonKey(name: r'net_cents', required: true, includeIfNull: false)
  final int netCents;

  @JsonKey(name: r'next_due_on', required: true, includeIfNull: true)
  final String? nextDueOn;

  @JsonKey(
    name: r'scope',
    required: true,
    includeIfNull: false,
    unknownEnumValue: ParentFeeStatementScopeEnum.unknownDefaultOpenApi,
  )
  final ParentFeeStatementScopeEnum scope;

  @JsonKey(name: r'charges', required: true, includeIfNull: false)
  final ParentFeeChargePage charges;

  @JsonKey(name: r'receipts', required: true, includeIfNull: false)
  final ParentFeeReceiptPage receipts;

  /// Original recorded invoice shares before credit notes. Optional for older servers.
  // minimum: 0
  @JsonKey(name: r'gross_charged_cents', required: false, includeIfNull: false)
  final int? grossChargedCents;

  /// Posted credit notes against these original shares. Optional for older servers.
  // minimum: 0
  @JsonKey(name: r'credited_cents', required: false, includeIfNull: false)
  final int? creditedCents;

  @JsonKey(name: r'credit_notes', required: false, includeIfNull: false)
  final ParentFeeCreditPage? creditNotes;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ParentFeeStatement &&
          other.id == id &&
          other.schoolId == schoolId &&
          other.holderUserId == holderUserId &&
          other.name == name &&
          other.reference == reference &&
          other.currency == currency &&
          other.chargedCents == chargedCents &&
          other.allocatedCents == allocatedCents &&
          other.outstandingCents == outstandingCents &&
          other.creditCents == creditCents &&
          other.netCents == netCents &&
          other.nextDueOn == nextDueOn &&
          other.scope == scope &&
          other.charges == charges &&
          other.receipts == receipts &&
          other.grossChargedCents == grossChargedCents &&
          other.creditedCents == creditedCents &&
          other.creditNotes == creditNotes;

  @override
  int get hashCode =>
      id.hashCode +
      schoolId.hashCode +
      holderUserId.hashCode +
      name.hashCode +
      reference.hashCode +
      currency.hashCode +
      chargedCents.hashCode +
      allocatedCents.hashCode +
      outstandingCents.hashCode +
      creditCents.hashCode +
      netCents.hashCode +
      (nextDueOn == null ? 0 : nextDueOn.hashCode) +
      scope.hashCode +
      charges.hashCode +
      receipts.hashCode +
      grossChargedCents.hashCode +
      creditedCents.hashCode +
      creditNotes.hashCode;

  factory ParentFeeStatement.fromJson(Map<String, dynamic> json) =>
      _$ParentFeeStatementFromJson(json);

  Map<String, dynamic> toJson() => _$ParentFeeStatementToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum ParentFeeStatementScopeEnum {
  @JsonValue(r'your_visible_ledger_records')
  yourVisibleLedgerRecords(r'your_visible_ledger_records'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const ParentFeeStatementScopeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
