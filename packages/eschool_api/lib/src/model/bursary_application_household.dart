//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'bursary_application_household.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class BursaryApplicationHousehold {
  /// Returns a new [BursaryApplicationHousehold] instance.
  BursaryApplicationHousehold({
    required this.householdSize,

    required this.dependants,

    required this.monthlyIncomeCents,

    required this.monthlyExpensesCents,

    this.otherSupport,

    required this.explanation,
  });

  @JsonKey(name: r'household_size', required: true, includeIfNull: false)
  final int householdSize;

  @JsonKey(name: r'dependants', required: true, includeIfNull: false)
  final int dependants;

  /// Integer cents.
  // minimum: 0
  @JsonKey(name: r'monthly_income_cents', required: true, includeIfNull: false)
  final int monthlyIncomeCents;

  /// Integer cents.
  // minimum: 0
  @JsonKey(
    name: r'monthly_expenses_cents',
    required: true,
    includeIfNull: false,
  )
  final int monthlyExpensesCents;

  @JsonKey(name: r'other_support', required: false, includeIfNull: false)
  final String? otherSupport;

  @JsonKey(name: r'explanation', required: true, includeIfNull: false)
  final String explanation;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is BursaryApplicationHousehold &&
          other.householdSize == householdSize &&
          other.dependants == dependants &&
          other.monthlyIncomeCents == monthlyIncomeCents &&
          other.monthlyExpensesCents == monthlyExpensesCents &&
          other.otherSupport == otherSupport &&
          other.explanation == explanation;

  @override
  int get hashCode =>
      householdSize.hashCode +
      dependants.hashCode +
      monthlyIncomeCents.hashCode +
      monthlyExpensesCents.hashCode +
      (otherSupport == null ? 0 : otherSupport.hashCode) +
      explanation.hashCode;

  factory BursaryApplicationHousehold.fromJson(Map<String, dynamic> json) =>
      _$BursaryApplicationHouseholdFromJson(json);

  Map<String, dynamic> toJson() => _$BursaryApplicationHouseholdToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
