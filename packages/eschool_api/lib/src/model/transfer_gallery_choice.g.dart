// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'transfer_gallery_choice.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TransferGalleryChoice _$TransferGalleryChoiceFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('TransferGalleryChoice', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['fingerprint', 'photos', 'can_keep']);
  final val = TransferGalleryChoice(
    fingerprint: $checkedConvert('fingerprint', (v) => v as String),
    photos: $checkedConvert('photos', (v) => (v as num).toInt()),
    canKeep: $checkedConvert('can_keep', (v) => v as bool),
  );
  return val;
}, fieldKeyMap: const {'canKeep': 'can_keep'});

Map<String, dynamic> _$TransferGalleryChoiceToJson(
  TransferGalleryChoice instance,
) => <String, dynamic>{
  'fingerprint': instance.fingerprint,
  'photos': instance.photos,
  'can_keep': instance.canKeep,
};
