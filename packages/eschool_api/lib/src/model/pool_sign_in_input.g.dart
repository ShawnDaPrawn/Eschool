// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'pool_sign_in_input.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PoolSignInInput _$PoolSignInInputFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'PoolSignInInput',
      json,
      ($checkedConvert) {
        $checkKeys(json, requiredKeys: const ['school', 'email', 'password']);
        final val = PoolSignInInput(
          school: $checkedConvert('school', (v) => v as String),
          email: $checkedConvert('email', (v) => v as String),
          password: $checkedConvert('password', (v) => v as String),
          newPassword: $checkedConvert('new_password', (v) => v as String?),
          newPasswordConfirmation: $checkedConvert(
            'new_password_confirmation',
            (v) => v as String?,
          ),
          acceptances: $checkedConvert(
            'acceptances',
            (v) => (v as Map<String, dynamic>?)?.map(
              (k, e) => MapEntry(k, e as String),
            ),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'newPassword': 'new_password',
        'newPasswordConfirmation': 'new_password_confirmation',
      },
    );

Map<String, dynamic> _$PoolSignInInputToJson(PoolSignInInput instance) =>
    <String, dynamic>{
      'school': instance.school,
      'email': instance.email,
      'password': instance.password,
      'new_password': ?instance.newPassword,
      'new_password_confirmation': ?instance.newPasswordConfirmation,
      'acceptances': ?instance.acceptances,
    };
