//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/wellbeing_case_entry_receipt_entry.dart';
import 'package:json_annotation/json_annotation.dart';

part 'wellbeing_case_entry_receipt.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WellbeingCaseEntryReceipt {
  /// Returns a new [WellbeingCaseEntryReceipt] instance.
  WellbeingCaseEntryReceipt({required this.caseRevision, required this.entry});

  // minimum: 1
  @JsonKey(name: r'case_revision', required: true, includeIfNull: false)
  final int caseRevision;

  @JsonKey(name: r'entry', required: true, includeIfNull: false)
  final WellbeingCaseEntryReceiptEntry entry;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WellbeingCaseEntryReceipt &&
          other.caseRevision == caseRevision &&
          other.entry == entry;

  @override
  int get hashCode => caseRevision.hashCode + entry.hashCode;

  factory WellbeingCaseEntryReceipt.fromJson(Map<String, dynamic> json) =>
      _$WellbeingCaseEntryReceiptFromJson(json);

  Map<String, dynamic> toJson() => _$WellbeingCaseEntryReceiptToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
