//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/risk_staff_choice.dart';
import 'package:json_annotation/json_annotation.dart';

part 'risk_professional_choices.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class RiskProfessionalChoices {
  /// Returns a new [RiskProfessionalChoices] instance.
  RiskProfessionalChoices({
    required this.page,

    required this.lastPage,

    required this.staff,
  });

  // minimum: 1
  @JsonKey(name: r'page', required: true, includeIfNull: false)
  final int page;

  // minimum: 1
  @JsonKey(name: r'last_page', required: true, includeIfNull: false)
  final int lastPage;

  @JsonKey(name: r'staff', required: true, includeIfNull: false)
  final List<RiskStaffChoice> staff;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is RiskProfessionalChoices &&
          other.page == page &&
          other.lastPage == lastPage &&
          other.staff == staff;

  @override
  int get hashCode => page.hashCode + lastPage.hashCode + staff.hashCode;

  factory RiskProfessionalChoices.fromJson(Map<String, dynamic> json) =>
      _$RiskProfessionalChoicesFromJson(json);

  Map<String, dynamic> toJson() => _$RiskProfessionalChoicesToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
