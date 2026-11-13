// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'sign_in_nonce_input.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SignInNonceInput _$SignInNonceInputFromJson(Map<String, dynamic> json) =>
    $checkedCreate('SignInNonceInput', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['school', 'app']);
      final val = SignInNonceInput(
        school: $checkedConvert('school', (v) => v as String),
        app: $checkedConvert(
          'app',
          (v) => $enumDecode(
            _$SignInNonceInputAppEnumEnumMap,
            v,
            unknownValue: SignInNonceInputAppEnum.unknownDefaultOpenApi,
          ),
        ),
      );
      return val;
    });

Map<String, dynamic> _$SignInNonceInputToJson(SignInNonceInput instance) =>
    <String, dynamic>{
      'school': instance.school,
      'app': _$SignInNonceInputAppEnumEnumMap[instance.app]!,
    };

const _$SignInNonceInputAppEnumEnumMap = {
  SignInNonceInputAppEnum.teacher: 'teacher',
  SignInNonceInputAppEnum.student: 'student',
  SignInNonceInputAppEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
