//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/task_insight_detail.dart';
import 'package:json_annotation/json_annotation.dart';

part 'task_insight_detail_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TaskInsightDetailResponse {
  /// Returns a new [TaskInsightDetailResponse] instance.
  TaskInsightDetailResponse({
    required this.error,

    required this.message,

    required this.data,

    required this.code,
  });

  @JsonKey(name: r'error', required: true, includeIfNull: false)
  final bool error;

  @JsonKey(name: r'message', required: true, includeIfNull: false)
  final String message;

  @JsonKey(name: r'data', required: true, includeIfNull: false)
  final TaskInsightDetail data;

  @JsonKey(
    name: r'code',
    required: true,
    includeIfNull: false,
    unknownEnumValue: TaskInsightDetailResponseCodeEnum.unknownDefaultOpenApi,
  )
  final TaskInsightDetailResponseCodeEnum code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TaskInsightDetailResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + data.hashCode + code.hashCode;

  factory TaskInsightDetailResponse.fromJson(Map<String, dynamic> json) =>
      _$TaskInsightDetailResponseFromJson(json);

  Map<String, dynamic> toJson() => _$TaskInsightDetailResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum TaskInsightDetailResponseCodeEnum {
  @JsonValue(200)
  number200('200'),
  @JsonValue(11184809)
  unknownDefaultOpenApi('11184809');

  const TaskInsightDetailResponseCodeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
