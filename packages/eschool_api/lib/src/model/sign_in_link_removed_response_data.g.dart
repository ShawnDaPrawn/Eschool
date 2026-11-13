// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'sign_in_link_removed_response_data.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SignInLinkRemovedResponseData _$SignInLinkRemovedResponseDataFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SignInLinkRemovedResponseData', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['removed']);
  final val = SignInLinkRemovedResponseData(
    removed: $checkedConvert('removed', (v) => v as bool),
  );
  return val;
});

Map<String, dynamic> _$SignInLinkRemovedResponseDataToJson(
  SignInLinkRemovedResponseData instance,
) => <String, dynamic>{'removed': instance.removed};
