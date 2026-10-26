//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'teaching_operation_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TeachingOperationWrite {
  /// Returns a new [TeachingOperationWrite] instance.
  TeachingOperationWrite({required this.operationId});

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TeachingOperationWrite && other.operationId == operationId;

  @override
  int get hashCode => operationId.hashCode;

  factory TeachingOperationWrite.fromJson(Map<String, dynamic> json) =>
      _$TeachingOperationWriteFromJson(json);

  Map<String, dynamic> toJson() => _$TeachingOperationWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
