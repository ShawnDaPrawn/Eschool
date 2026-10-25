//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'secure_card_portrait_receipt.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SecureCardPortraitReceipt {
  /// Returns a new [SecureCardPortraitReceipt] instance.
  SecureCardPortraitReceipt({
    required this.personId,

    required this.portraitId,

    required this.revision,
  });

  // minimum: 1
  @JsonKey(name: r'person_id', required: true, includeIfNull: false)
  final int personId;

  @JsonKey(name: r'portrait_id', required: true, includeIfNull: false)
  final String portraitId;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SecureCardPortraitReceipt &&
          other.personId == personId &&
          other.portraitId == portraitId &&
          other.revision == revision;

  @override
  int get hashCode =>
      personId.hashCode + portraitId.hashCode + revision.hashCode;

  factory SecureCardPortraitReceipt.fromJson(Map<String, dynamic> json) =>
      _$SecureCardPortraitReceiptFromJson(json);

  Map<String, dynamic> toJson() => _$SecureCardPortraitReceiptToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
