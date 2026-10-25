//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/cover_absence.dart';
import 'package:json_annotation/json_annotation.dart';

part 'cover_absence_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CoverAbsenceResponse {
  /// Returns a new [CoverAbsenceResponse] instance.
  CoverAbsenceResponse({
    required this.error,

    required this.message,

    required this.code,

    required this.data,
  });

  @JsonKey(name: r'error', required: true, includeIfNull: false)
  final bool error;

  @JsonKey(name: r'message', required: true, includeIfNull: false)
  final String message;

  @JsonKey(name: r'code', required: true, includeIfNull: false)
  final int code;

  @JsonKey(name: r'data', required: true, includeIfNull: false)
  final CoverAbsence data;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CoverAbsenceResponse &&
          other.error == error &&
          other.message == message &&
          other.code == code &&
          other.data == data;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + code.hashCode + data.hashCode;

  factory CoverAbsenceResponse.fromJson(Map<String, dynamic> json) =>
      _$CoverAbsenceResponseFromJson(json);

  Map<String, dynamic> toJson() => _$CoverAbsenceResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
