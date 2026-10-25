//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/weekly_digest_template.dart';
import 'package:eschool_api/src/model/weekly_digest_starting_template.dart';
import 'package:json_annotation/json_annotation.dart';

part 'weekly_digest_template_page.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WeeklyDigestTemplatePage {
  /// Returns a new [WeeklyDigestTemplatePage] instance.
  WeeklyDigestTemplatePage({
    required this.items,

    required this.startingTemplates,
  });

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<WeeklyDigestTemplate> items;

  @JsonKey(name: r'starting_templates', required: true, includeIfNull: false)
  final Map<String, WeeklyDigestStartingTemplate> startingTemplates;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WeeklyDigestTemplatePage &&
          other.items == items &&
          other.startingTemplates == startingTemplates;

  @override
  int get hashCode => items.hashCode + startingTemplates.hashCode;

  factory WeeklyDigestTemplatePage.fromJson(Map<String, dynamic> json) =>
      _$WeeklyDigestTemplatePageFromJson(json);

  Map<String, dynamic> toJson() => _$WeeklyDigestTemplatePageToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
