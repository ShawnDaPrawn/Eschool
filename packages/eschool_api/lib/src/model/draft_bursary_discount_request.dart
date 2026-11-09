//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'draft_bursary_discount_request.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class DraftBursaryDiscountRequest {
  /// Returns a new [DraftBursaryDiscountRequest] instance.
  DraftBursaryDiscountRequest({required this.operationId});

  /// Retry the same request with the same value.
  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is DraftBursaryDiscountRequest && other.operationId == operationId;

  @override
  int get hashCode => operationId.hashCode;

  factory DraftBursaryDiscountRequest.fromJson(Map<String, dynamic> json) =>
      _$DraftBursaryDiscountRequestFromJson(json);

  Map<String, dynamic> toJson() => _$DraftBursaryDiscountRequestToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
