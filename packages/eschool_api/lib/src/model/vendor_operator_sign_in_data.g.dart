// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'vendor_operator_sign_in_data.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

VendorOperatorSignInData _$VendorOperatorSignInDataFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('VendorOperatorSignInData', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const [
      'authenticated',
      'token',
      'profile',
      'password_change_required',
      'documents',
    ],
  );
  final val = VendorOperatorSignInData(
    authenticated: $checkedConvert('authenticated', (v) => v as bool),
    token: $checkedConvert('token', (v) => v as String?),
    profile: $checkedConvert(
      'profile',
      (v) => v == null
          ? null
          : VendorOperatorProfile.fromJson(v as Map<String, dynamic>),
    ),
    passwordChangeRequired: $checkedConvert(
      'password_change_required',
      (v) => v as bool,
    ),
    documents: $checkedConvert(
      'documents',
      (v) => (v as List<dynamic>)
          .map((e) => FacilitatorPolicy.fromJson(e as Map<String, dynamic>))
          .toList(),
    ),
  );
  return val;
}, fieldKeyMap: const {'passwordChangeRequired': 'password_change_required'});

Map<String, dynamic> _$VendorOperatorSignInDataToJson(
  VendorOperatorSignInData instance,
) => <String, dynamic>{
  'authenticated': instance.authenticated,
  'token': instance.token,
  'profile': instance.profile?.toJson(),
  'password_change_required': instance.passwordChangeRequired,
  'documents': instance.documents.map((e) => e.toJson()).toList(),
};
