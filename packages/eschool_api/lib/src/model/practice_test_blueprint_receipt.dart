//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'practice_test_blueprint_receipt.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PracticeTestBlueprintReceipt {
  /// Returns a new [PracticeTestBlueprintReceipt] instance.
  PracticeTestBlueprintReceipt({
    required this.id,

    required this.revision,

    required this.active,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'active', required: true, includeIfNull: false)
  final bool active;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PracticeTestBlueprintReceipt &&
          other.id == id &&
          other.revision == revision &&
          other.active == active;

  @override
  int get hashCode => id.hashCode + revision.hashCode + active.hashCode;

  factory PracticeTestBlueprintReceipt.fromJson(Map<String, dynamic> json) =>
      _$PracticeTestBlueprintReceiptFromJson(json);

  Map<String, dynamic> toJson() => _$PracticeTestBlueprintReceiptToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
