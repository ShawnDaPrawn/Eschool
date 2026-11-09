//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'submit_bursary_application_request.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SubmitBursaryApplicationRequest {
  /// Returns a new [SubmitBursaryApplicationRequest] instance.
  SubmitBursaryApplicationRequest({
    required this.operationId,

    required this.revision,

    required this.confirmed,
  });

  /// Retry the same request with the same value.
  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(
    name: r'confirmed',
    required: true,
    includeIfNull: false,
    fromJson: SubmitBursaryApplicationRequestConfirmedEnum.fromJson,
    toJson: SubmitBursaryApplicationRequestConfirmedEnum.toJsonValue,
  )
  final SubmitBursaryApplicationRequestConfirmedEnum confirmed;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SubmitBursaryApplicationRequest &&
          other.operationId == operationId &&
          other.revision == revision &&
          other.confirmed == confirmed;

  @override
  int get hashCode =>
      operationId.hashCode + revision.hashCode + confirmed.hashCode;

  factory SubmitBursaryApplicationRequest.fromJson(Map<String, dynamic> json) =>
      _$SubmitBursaryApplicationRequestFromJson(json);

  Map<String, dynamic> toJson() =>
      _$SubmitBursaryApplicationRequestToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum SubmitBursaryApplicationRequestConfirmedEnum {
  true_(true),
  unknownDefaultOpenApi(null);

  const SubmitBursaryApplicationRequestConfirmedEnum(this.value);
  final bool? value;

  static SubmitBursaryApplicationRequestConfirmedEnum fromJson(Object? value) =>
      values.firstWhere(
        (entry) => entry.value != null && entry.value == value,
        orElse: () =>
            SubmitBursaryApplicationRequestConfirmedEnum.unknownDefaultOpenApi,
      );
  static bool? toJsonValue(
    SubmitBursaryApplicationRequestConfirmedEnum value,
  ) => value.value;
}
