//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/recognition_point_category.dart';
import 'package:eschool_api/src/model/recognition_award_window.dart';
import 'package:eschool_api/src/model/recognition_settings.dart';
import 'package:eschool_api/src/model/recognition_house.dart';
import 'package:eschool_api/src/model/recognition_award_category.dart';
import 'package:eschool_api/src/model/recognition_named_reference.dart';
import 'package:eschool_api/src/model/recognition_year.dart';
import 'package:eschool_api/src/model/recognition_award_definition.dart';
import 'package:eschool_api/src/model/recognition_access.dart';
import 'package:json_annotation/json_annotation.dart';

part 'recognition_configuration.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class RecognitionConfiguration {
  /// Returns a new [RecognitionConfiguration] instance.
  RecognitionConfiguration({
    required this.access,

    required this.settings,

    required this.years,

    required this.grades,

    this.houses,

    this.pointCategories,

    this.awardCategories,

    this.awardDefinitions,

    this.awardWindows,
  });

  @JsonKey(name: r'access', required: true, includeIfNull: false)
  final RecognitionAccess access;

  @JsonKey(name: r'settings', required: true, includeIfNull: false)
  final RecognitionSettings settings;

  @JsonKey(name: r'years', required: true, includeIfNull: false)
  final List<RecognitionYear> years;

  @JsonKey(name: r'grades', required: true, includeIfNull: false)
  final List<RecognitionNamedReference> grades;

  @JsonKey(name: r'houses', required: false, includeIfNull: false)
  final List<RecognitionHouse>? houses;

  @JsonKey(name: r'point_categories', required: false, includeIfNull: false)
  final List<RecognitionPointCategory>? pointCategories;

  @JsonKey(name: r'award_categories', required: false, includeIfNull: false)
  final List<RecognitionAwardCategory>? awardCategories;

  @JsonKey(name: r'award_definitions', required: false, includeIfNull: false)
  final List<RecognitionAwardDefinition>? awardDefinitions;

  @JsonKey(name: r'award_windows', required: false, includeIfNull: false)
  final List<RecognitionAwardWindow>? awardWindows;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is RecognitionConfiguration &&
          other.access == access &&
          other.settings == settings &&
          other.years == years &&
          other.grades == grades &&
          other.houses == houses &&
          other.pointCategories == pointCategories &&
          other.awardCategories == awardCategories &&
          other.awardDefinitions == awardDefinitions &&
          other.awardWindows == awardWindows;

  @override
  int get hashCode =>
      access.hashCode +
      settings.hashCode +
      years.hashCode +
      grades.hashCode +
      houses.hashCode +
      pointCategories.hashCode +
      awardCategories.hashCode +
      awardDefinitions.hashCode +
      awardWindows.hashCode;

  factory RecognitionConfiguration.fromJson(Map<String, dynamic> json) =>
      _$RecognitionConfigurationFromJson(json);

  Map<String, dynamic> toJson() => _$RecognitionConfigurationToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
