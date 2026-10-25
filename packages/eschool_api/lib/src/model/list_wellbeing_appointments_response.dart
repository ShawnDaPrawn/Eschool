//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/wellbeing_appointment.dart';
import 'package:json_annotation/json_annotation.dart';

part 'list_wellbeing_appointments_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ListWellbeingAppointmentsResponse {
  /// Returns a new [ListWellbeingAppointmentsResponse] instance.
  ListWellbeingAppointmentsResponse({
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
  final List<WellbeingAppointment> data;

  @JsonKey(name: r'code', required: true, includeIfNull: false)
  final int code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ListWellbeingAppointmentsResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + data.hashCode + code.hashCode;

  factory ListWellbeingAppointmentsResponse.fromJson(
    Map<String, dynamic> json,
  ) => _$ListWellbeingAppointmentsResponseFromJson(json);

  Map<String, dynamic> toJson() =>
      _$ListWellbeingAppointmentsResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
