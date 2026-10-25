//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/progress_released_band.dart';
import 'package:json_annotation/json_annotation.dart';

part 'progress_family_bands.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ProgressFamilyBands {
  /// Returns a new [ProgressFamilyBands] instance.
  ProgressFamilyBands({
    required this.audience,

    required this.learnerId,

    required this.results,
  });

  @JsonKey(
    name: r'audience',
    required: true,
    includeIfNull: false,
    unknownEnumValue: ProgressFamilyBandsAudienceEnum.unknownDefaultOpenApi,
  )
  final ProgressFamilyBandsAudienceEnum audience;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(name: r'results', required: true, includeIfNull: false)
  final List<ProgressReleasedBand> results;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ProgressFamilyBands &&
          other.audience == audience &&
          other.learnerId == learnerId &&
          other.results == results;

  @override
  int get hashCode => audience.hashCode + learnerId.hashCode + results.hashCode;

  factory ProgressFamilyBands.fromJson(Map<String, dynamic> json) =>
      _$ProgressFamilyBandsFromJson(json);

  Map<String, dynamic> toJson() => _$ProgressFamilyBandsToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum ProgressFamilyBandsAudienceEnum {
  @JsonValue(r'family')
  family(r'family'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const ProgressFamilyBandsAudienceEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
