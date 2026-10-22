// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'validation_errors.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ValidationErrors _$ValidationErrorsFromJson(Map<String, dynamic> json) =>
    $checkedCreate('ValidationErrors', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['errors']);
      final val = ValidationErrors(
        errors: $checkedConvert(
          'errors',
          (v) => (v as Map<String, dynamic>).map(
            (k, e) => MapEntry(
              k,
              (e as List<dynamic>).map((e) => e as String).toList(),
            ),
          ),
        ),
      );
      return val;
    });

Map<String, dynamic> _$ValidationErrorsToJson(ValidationErrors instance) =>
    <String, dynamic>{'errors': instance.errors};
