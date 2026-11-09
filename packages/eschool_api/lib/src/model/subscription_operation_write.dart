//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'subscription_operation_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SubscriptionOperationWrite {
  /// Returns a new [SubscriptionOperationWrite] instance.
  SubscriptionOperationWrite({required this.operationId});

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SubscriptionOperationWrite && other.operationId == operationId;

  @override
  int get hashCode => operationId.hashCode;

  factory SubscriptionOperationWrite.fromJson(Map<String, dynamic> json) =>
      _$SubscriptionOperationWriteFromJson(json);

  Map<String, dynamic> toJson() => _$SubscriptionOperationWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
