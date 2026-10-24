//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'content_read_receipt.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ContentReadReceipt {
  /// Returns a new [ContentReadReceipt] instance.
  ContentReadReceipt({
    required this.versionId,

    required this.learnerId,

    required this.read,
  });

  // minimum: 1
  @JsonKey(name: r'version_id', required: true, includeIfNull: false)
  final int versionId;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(name: r'read', required: true, includeIfNull: false)
  final bool read;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ContentReadReceipt &&
          other.versionId == versionId &&
          other.learnerId == learnerId &&
          other.read == read;

  @override
  int get hashCode => versionId.hashCode + learnerId.hashCode + read.hashCode;

  factory ContentReadReceipt.fromJson(Map<String, dynamic> json) =>
      _$ContentReadReceiptFromJson(json);

  Map<String, dynamic> toJson() => _$ContentReadReceiptToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
