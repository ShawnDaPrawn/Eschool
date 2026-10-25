//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/discipline_supervisors.dart';
import 'package:json_annotation/json_annotation.dart';

part 'discipline_supervisors_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class DisciplineSupervisorsResponse {
  /// Returns a new [DisciplineSupervisorsResponse] instance.
  DisciplineSupervisorsResponse({
    required this.error,

    required this.message,

    required this.code,

    required this.data,
  });

  @JsonKey(name: r'error', required: true, includeIfNull: false)
  final bool error;

  @JsonKey(name: r'message', required: true, includeIfNull: false)
  final String message;

  // minimum: 0
  @JsonKey(name: r'code', required: true, includeIfNull: false)
  final int code;

  @JsonKey(name: r'data', required: true, includeIfNull: false)
  final DisciplineSupervisors data;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is DisciplineSupervisorsResponse &&
          other.error == error &&
          other.message == message &&
          other.code == code &&
          other.data == data;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + code.hashCode + data.hashCode;

  factory DisciplineSupervisorsResponse.fromJson(Map<String, dynamic> json) =>
      _$DisciplineSupervisorsResponseFromJson(json);

  Map<String, dynamic> toJson() => _$DisciplineSupervisorsResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
