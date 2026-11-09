//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'learner_passport_export_availability.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class LearnerPassportExportAvailability {
  /// Returns a new [LearnerPassportExportAvailability] instance.
  LearnerPassportExportAvailability({
    required this.available,

    required this.formats,
  });

  @JsonKey(name: r'available', required: true, includeIfNull: false)
  final bool available;

  @JsonKey(
    name: r'formats',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        LearnerPassportExportAvailabilityFormatsEnum.unknownDefaultOpenApi,
  )
  final List<LearnerPassportExportAvailabilityFormatsEnum> formats;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is LearnerPassportExportAvailability &&
          other.available == available &&
          other.formats == formats;

  @override
  int get hashCode => available.hashCode + formats.hashCode;

  factory LearnerPassportExportAvailability.fromJson(
    Map<String, dynamic> json,
  ) => _$LearnerPassportExportAvailabilityFromJson(json);

  Map<String, dynamic> toJson() =>
      _$LearnerPassportExportAvailabilityToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum LearnerPassportExportAvailabilityFormatsEnum {
  @JsonValue(r'json')
  json(r'json'),
  @JsonValue(r'pdf')
  pdf(r'pdf'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const LearnerPassportExportAvailabilityFormatsEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
