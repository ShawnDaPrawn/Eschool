//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/strengths_section_key.dart';
import 'package:json_annotation/json_annotation.dart';

part 'strengths_share_input.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class StrengthsShareInput {
  /// Returns a new [StrengthsShareInput] instance.
  StrengthsShareInput({
    required this.operationId,

    required this.recipientUserId,

    required this.sections,

    this.days,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  /// A user_id from listStrengthsRecipients.
  // minimum: 1
  @JsonKey(name: r'recipient_user_id', required: true, includeIfNull: false)
  final int recipientUserId;

  @JsonKey(name: r'sections', required: true, includeIfNull: false)
  final Set<StrengthsSectionKey> sections;

  /// How long the share lasts; 30 when left out.
  // minimum: 1
  // maximum: 30
  @JsonKey(name: r'days', required: false, includeIfNull: false)
  final int? days;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is StrengthsShareInput &&
          other.operationId == operationId &&
          other.recipientUserId == recipientUserId &&
          other.sections == sections &&
          other.days == days;

  @override
  int get hashCode =>
      operationId.hashCode +
      recipientUserId.hashCode +
      sections.hashCode +
      days.hashCode;

  factory StrengthsShareInput.fromJson(Map<String, dynamic> json) =>
      _$StrengthsShareInputFromJson(json);

  Map<String, dynamic> toJson() => _$StrengthsShareInputToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
