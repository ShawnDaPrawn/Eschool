import 'package:cached_network_image/cached_network_image.dart';
import 'package:eschool/ui/screens/exam/onlineExam/models/question.dart';
import 'package:eschool/ui/widgets/customCircularProgressIndicator.dart';
import 'package:eschool/utils/extensions.dart';
import 'package:eschool/utils/labelKeys.dart';
import 'package:eschool/utils/uiUtils.dart';
import 'package:flutter/material.dart';
import 'package:flutter_tex/flutter_tex.dart';

class QuestionContainer extends StatelessWidget {

  const QuestionContainer({
    required this.isMathQuestion, super.key,
    this.question,
    this.questionColor,
    this.questionNumber,
    this.note,
  });
  final Question? question;
  final Color? questionColor;
  final int? questionNumber;
  final bool isMathQuestion;
  final String? note;

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Flexible(
              child: Container(
                alignment: Alignment.centerLeft,
                padding: EdgeInsets.symmetric(
                  horizontal: MediaQuery.sizeOf(context).width * 0.089,
                ),
                child: isMathQuestion
                    ? TeXView(
                        child: TeXViewDocument(
                          questionNumber == null
                              ? '${question!.question}'
                              : '$questionNumber. ${question!.question}',
                        ),
                        style: TeXViewStyle(
                          contentColor:
                              questionColor ?? Theme.of(context).primaryColor,
                          backgroundColor: Colors.transparent,
                          sizeUnit: TeXViewSizeUnit.pixels,
                          fontStyle: TeXViewFontStyle(fontSize: 20),
                        ),
                        renderingEngine: const TeXViewRenderingEngine.katex(),
                      )
                    : Text(
                        questionNumber == null
                            ? '${question!.question}'
                            : '$questionNumber. ${question!.question}',
                        style: TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.w400,
                          color:
                              questionColor ?? Theme.of(context).primaryColor,
                        ),
                      ),
              ),
            ),
            question!.marks!.isEmpty
                ? const SizedBox.shrink()
                : Padding(
                    padding: const EdgeInsets.only(right: 20),
                    child: Text(
                      '[${question!.marks} ${UiUtils.getTranslatedLabel(context, marksKey)}]',
                      style: TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.w400,
                        color: questionColor ?? Theme.of(context).primaryColor,
                      ),
                    ),
                  ),
          ],
        ),
        15.sizedBoxHeight,
        question!.imageUrl == null
            ? const SizedBox.shrink()
            : question!.imageUrl!.isEmpty
            ? const SizedBox.shrink()
            : Container(
                width: MediaQuery.sizeOf(context).width * 0.8,
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(25),
                ),
                height: MediaQuery.sizeOf(context).height * 0.225,
                child: CachedNetworkImage(
                  errorWidget: (context, image, _) => Center(
                    child: Icon(
                      Icons.error,
                      color: Theme.of(context).primaryColor,
                    ),
                  ),
                  imageBuilder: (context, imageProvider) {
                    return Container(
                      decoration: BoxDecoration(
                        image: DecorationImage(
                          image: imageProvider,
                          fit: BoxFit.cover,
                        ),
                        borderRadius: BorderRadius.circular(25),
                      ),
                    );
                  },
                  imageUrl: question!.imageUrl!,
                  placeholder: (context, url) => Center(
                    child: CustomCircularProgressIndicator(
                      indicatorColor: UiUtils.getColorScheme(context).primary,
                    ),
                  ),
                ),
              ),
        note == '' ? const SizedBox.shrink() : 8.sizedBoxHeight,
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 20),
          child: Row(
            children: [
              Flexible(
                child: (note == '')
                    ? const SizedBox.shrink()
                    : Text(
                        '$note',
                        style: const TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.normal,
                        ),
                      ),
              ),
            ],
          ),
        ),
        5.sizedBoxHeight,
      ],
    );
  }
}
