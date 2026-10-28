//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'commerce_cancel_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CommerceCancelWrite {
  /// Returns a new [CommerceCancelWrite] instance.
  CommerceCancelWrite({
    required this.operationId,

    required this.privateReason,

    required this.confirmed,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @JsonKey(name: r'private_reason', required: true, includeIfNull: false)
  final String privateReason;

  /// Must be true to cancel an uncollected order and return its original wallet money and stock once.
  @JsonKey(name: r'confirmed', required: true, includeIfNull: false)
  final bool confirmed;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CommerceCancelWrite &&
          other.operationId == operationId &&
          other.privateReason == privateReason &&
          other.confirmed == confirmed;

  @override
  int get hashCode =>
      operationId.hashCode + privateReason.hashCode + confirmed.hashCode;

  factory CommerceCancelWrite.fromJson(Map<String, dynamic> json) =>
      _$CommerceCancelWriteFromJson(json);

  Map<String, dynamic> toJson() => _$CommerceCancelWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
