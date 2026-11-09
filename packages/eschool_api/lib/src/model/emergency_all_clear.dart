//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'emergency_all_clear.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class EmergencyAllClear {
  /// Returns a new [EmergencyAllClear] instance.
  EmergencyAllClear({
    required this.operationId,

    required this.revision,

    this.message,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'message', required: false, includeIfNull: false)
  final String? message;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is EmergencyAllClear &&
          other.operationId == operationId &&
          other.revision == revision &&
          other.message == message;

  @override
  int get hashCode =>
      operationId.hashCode +
      revision.hashCode +
      (message == null ? 0 : message.hashCode);

  factory EmergencyAllClear.fromJson(Map<String, dynamic> json) =>
      _$EmergencyAllClearFromJson(json);

  Map<String, dynamic> toJson() => _$EmergencyAllClearToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
