//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/wellbeing_case_page.dart';
import 'package:json_annotation/json_annotation.dart';

part 'list_wellbeing_cases_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ListWellbeingCasesResponse {
  /// Returns a new [ListWellbeingCasesResponse] instance.
  ListWellbeingCasesResponse({
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
  final WellbeingCasePage data;

  @JsonKey(name: r'code', required: true, includeIfNull: false)
  final int code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ListWellbeingCasesResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + data.hashCode + code.hashCode;

  factory ListWellbeingCasesResponse.fromJson(Map<String, dynamic> json) =>
      _$ListWellbeingCasesResponseFromJson(json);

  Map<String, dynamic> toJson() => _$ListWellbeingCasesResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
