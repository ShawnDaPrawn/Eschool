//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'school_assistance_unavailable_response_data.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SchoolAssistanceUnavailableResponseData {
  /// Returns a new [SchoolAssistanceUnavailableResponseData] instance.
  SchoolAssistanceUnavailableResponseData({required this.reason});

  @JsonKey(name: r'reason', required: true, includeIfNull: false)
  final String reason;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SchoolAssistanceUnavailableResponseData &&
          other.reason == reason;

  @override
  int get hashCode => reason.hashCode;

  factory SchoolAssistanceUnavailableResponseData.fromJson(
    Map<String, dynamic> json,
  ) => _$SchoolAssistanceUnavailableResponseDataFromJson(json);

  Map<String, dynamic> toJson() =>
      _$SchoolAssistanceUnavailableResponseDataToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
