//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/term_template.dart';
import 'package:json_annotation/json_annotation.dart';

part 'term_templates_response_data.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TermTemplatesResponseData {
  /// Returns a new [TermTemplatesResponseData] instance.
  TermTemplatesResponseData({required this.templates});

  @JsonKey(name: r'templates', required: true, includeIfNull: false)
  final List<TermTemplate> templates;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TermTemplatesResponseData && other.templates == templates;

  @override
  int get hashCode => templates.hashCode;

  factory TermTemplatesResponseData.fromJson(Map<String, dynamic> json) =>
      _$TermTemplatesResponseDataFromJson(json);

  Map<String, dynamic> toJson() => _$TermTemplatesResponseDataToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
