//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'bursary_household_input.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class BursaryHouseholdInput {
  /// Returns a new [BursaryHouseholdInput] instance.
  BursaryHouseholdInput({
    required this.householdSize,

    required this.dependants,

    required this.monthlyIncomeCents,

    required this.monthlyExpensesCents,

    this.otherSupport,

    required this.explanation,
  });

  // minimum: 1
  // maximum: 30
  @JsonKey(name: r'household_size', required: true, includeIfNull: false)
  final int householdSize;

  // minimum: 0
  // maximum: 30
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
      other is BursaryHouseholdInput &&
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

  factory BursaryHouseholdInput.fromJson(Map<String, dynamic> json) =>
      _$BursaryHouseholdInputFromJson(json);

  Map<String, dynamic> toJson() => _$BursaryHouseholdInputToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
