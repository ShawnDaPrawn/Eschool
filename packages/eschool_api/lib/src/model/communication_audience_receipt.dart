//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'communication_audience_receipt.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CommunicationAudienceReceipt {
  /// Returns a new [CommunicationAudienceReceipt] instance.
  CommunicationAudienceReceipt({required this.id, required this.revision});

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CommunicationAudienceReceipt &&
          other.id == id &&
          other.revision == revision;

  @override
  int get hashCode => id.hashCode + revision.hashCode;

  factory CommunicationAudienceReceipt.fromJson(Map<String, dynamic> json) =>
      _$CommunicationAudienceReceiptFromJson(json);

  Map<String, dynamic> toJson() => _$CommunicationAudienceReceiptToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
