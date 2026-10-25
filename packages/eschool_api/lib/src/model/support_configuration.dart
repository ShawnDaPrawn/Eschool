//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/support_named_choice.dart';
import 'package:json_annotation/json_annotation.dart';

part 'support_configuration.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SupportConfiguration {
  /// Returns a new [SupportConfiguration] instance.
  SupportConfiguration({
    required this.audience,

    required this.page,

    required this.lastPage,

    required this.learners,

    required this.staff,

    required this.staffLastPage,
  });

  @JsonKey(
    name: r'audience',
    required: true,
    includeIfNull: false,
    unknownEnumValue: SupportConfigurationAudienceEnum.unknownDefaultOpenApi,
  )
  final SupportConfigurationAudienceEnum audience;

  // minimum: 1
  @JsonKey(name: r'page', required: true, includeIfNull: false)
  final int page;

  // minimum: 1
  @JsonKey(name: r'last_page', required: true, includeIfNull: false)
  final int lastPage;

  @JsonKey(name: r'learners', required: true, includeIfNull: false)
  final List<SupportNamedChoice> learners;

  @JsonKey(name: r'staff', required: true, includeIfNull: false)
  final List<SupportNamedChoice> staff;

  // minimum: 1
  @JsonKey(name: r'staff_last_page', required: true, includeIfNull: false)
  final int staffLastPage;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SupportConfiguration &&
          other.audience == audience &&
          other.page == page &&
          other.lastPage == lastPage &&
          other.learners == learners &&
          other.staff == staff &&
          other.staffLastPage == staffLastPage;

  @override
  int get hashCode =>
      audience.hashCode +
      page.hashCode +
      lastPage.hashCode +
      learners.hashCode +
      staff.hashCode +
      staffLastPage.hashCode;

  factory SupportConfiguration.fromJson(Map<String, dynamic> json) =>
      _$SupportConfigurationFromJson(json);

  Map<String, dynamic> toJson() => _$SupportConfigurationToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum SupportConfigurationAudienceEnum {
  @JsonValue(r'principal')
  principal(r'principal'),
  @JsonValue(r'professional')
  professional(r'professional'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const SupportConfigurationAudienceEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
