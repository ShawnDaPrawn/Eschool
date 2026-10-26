//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'tutor_operation_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TutorOperationWrite {
  /// Returns a new [TutorOperationWrite] instance.
  TutorOperationWrite({required this.operationId});

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TutorOperationWrite && other.operationId == operationId;

  @override
  int get hashCode => operationId.hashCode;

  factory TutorOperationWrite.fromJson(Map<String, dynamic> json) =>
      _$TutorOperationWriteFromJson(json);

  Map<String, dynamic> toJson() => _$TutorOperationWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
