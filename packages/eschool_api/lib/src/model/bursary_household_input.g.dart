// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'bursary_household_input.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

BursaryHouseholdInput _$BursaryHouseholdInputFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'BursaryHouseholdInput',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'household_size',
        'dependants',
        'monthly_income_cents',
        'monthly_expenses_cents',
        'explanation',
      ],
    );
    final val = BursaryHouseholdInput(
      householdSize: $checkedConvert(
        'household_size',
        (v) => (v as num).toInt(),
      ),
      dependants: $checkedConvert('dependants', (v) => (v as num).toInt()),
      monthlyIncomeCents: $checkedConvert(
        'monthly_income_cents',
        (v) => (v as num).toInt(),
      ),
      monthlyExpensesCents: $checkedConvert(
        'monthly_expenses_cents',
        (v) => (v as num).toInt(),
      ),
      otherSupport: $checkedConvert('other_support', (v) => v as String?),
      explanation: $checkedConvert('explanation', (v) => v as String),
    );
    return val;
  },
  fieldKeyMap: const {
    'householdSize': 'household_size',
    'monthlyIncomeCents': 'monthly_income_cents',
    'monthlyExpensesCents': 'monthly_expenses_cents',
    'otherSupport': 'other_support',
  },
);

Map<String, dynamic> _$BursaryHouseholdInputToJson(
  BursaryHouseholdInput instance,
) => <String, dynamic>{
  'household_size': instance.householdSize,
  'dependants': instance.dependants,
  'monthly_income_cents': instance.monthlyIncomeCents,
  'monthly_expenses_cents': instance.monthlyExpensesCents,
  'other_support': ?instance.otherSupport,
  'explanation': instance.explanation,
};
