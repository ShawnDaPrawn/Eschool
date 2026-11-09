//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'pool_cancel_input.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PoolCancelInput {
  /// Returns a new [PoolCancelInput] instance.
  PoolCancelInput({required this.operationId, required this.revision});

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 0
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PoolCancelInput &&
          other.operationId == operationId &&
          other.revision == revision;

  @override
  int get hashCode => operationId.hashCode + revision.hashCode;

  factory PoolCancelInput.fromJson(Map<String, dynamic> json) =>
      _$PoolCancelInputFromJson(json);

  Map<String, dynamic> toJson() => _$PoolCancelInputToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
