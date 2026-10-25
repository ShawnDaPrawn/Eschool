// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'recognition_nomination_catalogue.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

RecognitionNominationCatalogue _$RecognitionNominationCatalogueFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('RecognitionNominationCatalogue', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['items']);
  final val = RecognitionNominationCatalogue(
    items: $checkedConvert(
      'items',
      (v) => (v as List<dynamic>)
          .map(
            (e) =>
                RecognitionNominationChoice.fromJson(e as Map<String, dynamic>),
          )
          .toList(),
    ),
  );
  return val;
});

Map<String, dynamic> _$RecognitionNominationCatalogueToJson(
  RecognitionNominationCatalogue instance,
) => <String, dynamic>{'items': instance.items.map((e) => e.toJson()).toList()};
