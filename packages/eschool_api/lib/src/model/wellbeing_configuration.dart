//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/wellbeing_category.dart';
import 'package:eschool_api/src/model/wellbeing_named_choice.dart';
import 'package:eschool_api/src/model/wellbeing_learner_choice.dart';
import 'package:json_annotation/json_annotation.dart';

part 'wellbeing_configuration.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WellbeingConfiguration {
  /// Returns a new [WellbeingConfiguration] instance.
  WellbeingConfiguration({
    required this.professionalChoices,

    required this.professionalsLastPage,

    required this.learnerChoices,

    required this.learnersLastPage,

    required this.categories,

    required this.categoriesLastPage,

    required this.page,
  });

  @JsonKey(name: r'professional_choices', required: true, includeIfNull: false)
  final List<WellbeingNamedChoice> professionalChoices;

  // minimum: 1
  @JsonKey(
    name: r'professionals_last_page',
    required: true,
    includeIfNull: false,
  )
  final int professionalsLastPage;

  @JsonKey(name: r'learner_choices', required: true, includeIfNull: false)
  final List<WellbeingLearnerChoice> learnerChoices;

  // minimum: 1
  @JsonKey(name: r'learners_last_page', required: true, includeIfNull: false)
  final int learnersLastPage;

  @JsonKey(name: r'categories', required: true, includeIfNull: false)
  final List<WellbeingCategory> categories;

  // minimum: 1
  @JsonKey(name: r'categories_last_page', required: true, includeIfNull: false)
  final int categoriesLastPage;

  // minimum: 1
  @JsonKey(name: r'page', required: true, includeIfNull: false)
  final int page;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WellbeingConfiguration &&
          other.professionalChoices == professionalChoices &&
          other.professionalsLastPage == professionalsLastPage &&
          other.learnerChoices == learnerChoices &&
          other.learnersLastPage == learnersLastPage &&
          other.categories == categories &&
          other.categoriesLastPage == categoriesLastPage &&
          other.page == page;

  @override
  int get hashCode =>
      professionalChoices.hashCode +
      professionalsLastPage.hashCode +
      learnerChoices.hashCode +
      learnersLastPage.hashCode +
      categories.hashCode +
      categoriesLastPage.hashCode +
      page.hashCode;

  factory WellbeingConfiguration.fromJson(Map<String, dynamic> json) =>
      _$WellbeingConfigurationFromJson(json);

  Map<String, dynamic> toJson() => _$WellbeingConfigurationToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
