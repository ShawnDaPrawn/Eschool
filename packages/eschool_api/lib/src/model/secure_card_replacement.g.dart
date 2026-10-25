// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'secure_card_replacement.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SecureCardReplacement _$SecureCardReplacementFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SecureCardReplacement', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['previous', 'current']);
  final val = SecureCardReplacement(
    previous: $checkedConvert(
      'previous',
      (v) => SecureCard.fromJson(v as Map<String, dynamic>),
    ),
    current: $checkedConvert(
      'current',
      (v) => SecureCard.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$SecureCardReplacementToJson(
  SecureCardReplacement instance,
) => <String, dynamic>{
  'previous': instance.previous.toJson(),
  'current': instance.current.toJson(),
};
