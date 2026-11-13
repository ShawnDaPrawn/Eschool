// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'sign_in_link_input.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SignInLinkInput _$SignInLinkInputFromJson(Map<String, dynamic> json) =>
    $checkedCreate('SignInLinkInput', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['id_token', 'nonce', 'password']);
      final val = SignInLinkInput(
        idToken: $checkedConvert('id_token', (v) => v as String),
        nonce: $checkedConvert('nonce', (v) => v as String),
        password: $checkedConvert('password', (v) => v as String),
      );
      return val;
    }, fieldKeyMap: const {'idToken': 'id_token'});

Map<String, dynamic> _$SignInLinkInputToJson(SignInLinkInput instance) =>
    <String, dynamic>{
      'id_token': instance.idToken,
      'nonce': instance.nonce,
      'password': instance.password,
    };
