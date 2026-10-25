//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/support_plan.dart';
import 'package:json_annotation/json_annotation.dart';

part 'support_plan_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SupportPlanResponse {
  /// Returns a new [SupportPlanResponse] instance.
  SupportPlanResponse({
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
  final SupportPlan data;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SupportPlanResponse &&
          other.error == error &&
          other.message == message &&
          other.code == code &&
          other.data == data;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + code.hashCode + data.hashCode;

  factory SupportPlanResponse.fromJson(Map<String, dynamic> json) =>
      _$SupportPlanResponseFromJson(json);

  Map<String, dynamic> toJson() => _$SupportPlanResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
