// ignore_for_file: public_member_api_docs, sort_constructors_first
import 'package:eschool/cubits/authCubit.dart';
import 'package:eschool/cubits/feesTransactionCubit.dart';
import 'package:eschool/data/repositories/studentRepository.dart';
import 'package:eschool/ui/widgets/customBackButton.dart';
import 'package:eschool/ui/widgets/customRefreshIndicator.dart';
import 'package:eschool/ui/widgets/customShimmerContainer.dart';
import 'package:eschool/ui/widgets/errorContainer.dart';
import 'package:eschool/ui/widgets/loadMoreErrorWidget.dart';
import 'package:eschool/ui/widgets/noDataContainer.dart';
import 'package:eschool/ui/widgets/screenTopBackgroundContainer.dart';
import 'package:eschool/ui/widgets/shimmerLoadingContainer.dart';
import 'package:eschool/utils/animationConfiguration.dart';
import 'package:eschool/utils/labelKeys.dart';
import 'package:eschool/utils/uiUtils.dart';
import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:eschool/ui/screens/fees/widgets/feeTransactionItemContainer.dart';

class FeesTransactionScreen extends StatefulWidget {
  const FeesTransactionScreen({super.key});

  static Route route(RouteSettings routeSettings) {
    return CupertinoPageRoute(
      builder: (_) => BlocProvider(
        create: (context) => FeesTransactionsCubit(StudentRepository()),
        child: const FeesTransactionScreen(),
      ),
    );
  }

  @override
  State<FeesTransactionScreen> createState() => _FeesTransactionScreenState();
}

class _FeesTransactionScreenState extends State<FeesTransactionScreen> {
  late final ScrollController _scrollController = ScrollController()
    ..addListener(_feesTransactionScrollListener);

  void _feesTransactionScrollListener() {
    if (_scrollController.offset >=
        _scrollController.position.maxScrollExtent) {
      if (context.read<FeesTransactionsCubit>().hasMore()) {
        context.read<FeesTransactionsCubit>().fetchMoreFeesTransactions(
          isStudentLoggedIn: !context.read<AuthCubit>().isParent(),
        );
      }
    }
  }

  @override
  void initState() {
    Future.delayed(Duration.zero, () {
      fetchFeesTransactions();
    });
    super.initState();
  }

  @override
  void dispose() {
    _scrollController.removeListener(_feesTransactionScrollListener);
    _scrollController.dispose();
    super.dispose();
  }

  void fetchFeesTransactions() {
    context.read<FeesTransactionsCubit>().fetchFeesTransactions(
      page: 1,
      isStudentLoggedIn: !context.read<AuthCubit>().isParent(),
    );
  }

  Widget _buildAppBar(BuildContext context) {
    return ScreenTopBackgroundContainer(
      heightPercentage: UiUtils.appBarSmallerHeightPercentage,
      child: Stack(
        clipBehavior: Clip.none,
        children: [
          const CustomBackButton(),
          Align(
            alignment: Alignment.topCenter,
            child: Text(
              UiUtils.getTranslatedLabel(context, feesTransactionKey),
              style: TextStyle(
                color: Theme.of(context).colorScheme.onInverseSurface,
                fontSize: UiUtils.screenTitleFontSize,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildShimmerLoader() {
    return ShimmerLoadingContainer(
      child: LayoutBuilder(
        builder: (context, boxConstraints) {
          return SizedBox(
            height: double.maxFinite,
            child: ListView.builder(
              padding: EdgeInsets.zero,
              physics: const NeverScrollableScrollPhysics(),
              itemCount: UiUtils.defaultShimmerLoadingContentCount,
              itemBuilder: (context, index) {
                return _buildOneTransactionShimmerLoader();
              },
            ),
          );
        },
      ),
    );
  }

  Widget _buildOneTransactionShimmerLoader() {
    return Padding(
      padding: EdgeInsets.symmetric(
        vertical: 8,
        horizontal: MediaQuery.sizeOf(context).width * 0.075,
      ),
      child: const ShimmerLoadingContainer(
        child: CustomShimmerContainer(height: 100, borderRadius: 10),
      ),
    );
  }

  // The _buildSingleTransactionItem widget was extracted into lib/ui/screens/fees/widgets/feeTransactionItemContainer.dart

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Stack(
        children: [
          BlocBuilder<FeesTransactionsCubit, FeesTransactionState>(
            builder: (context, state) {
              if (state is FeesTransactionFetchSuccess) {
                return state.feesTransactions.isEmpty
                    ? const NoDataContainer(
                        titleKey: noFeesTransactionsFoundKey,
                      )
                    : CustomRefreshIndicator(
                        displacement: UiUtils.getScrollViewTopPadding(
                          context: context,
                          appBarHeightPercentage:
                              UiUtils.appBarSmallerHeightPercentage,
                        ),
                        onRefreshCallback: () {
                          if (!context
                              .read<FeesTransactionsCubit>()
                              .isLoading()) {
                            fetchFeesTransactions();
                          }
                        },
                        child: SizedBox(
                          height: double.maxFinite,
                          width: double.maxFinite,
                          child: SingleChildScrollView(
                            controller: _scrollController,
                            physics: const AlwaysScrollableScrollPhysics(),
                            padding: EdgeInsets.only(
                              top: UiUtils.getScrollViewTopPadding(
                                context: context,
                                appBarHeightPercentage:
                                    UiUtils.appBarSmallerHeightPercentage,
                              ),
                              bottom: UiUtils.getScrollViewBottomPadding(
                                context,
                              ),
                            ),
                            child: Column(
                              children:
                                  List.generate(state.feesTransactions.length, (
                                    index,
                                  ) {
                                    {
                                      return Animate(
                                        effects: listItemAppearanceEffects(
                                          itemIndex: index,
                                        ),
                                        child: FeeTransactionItemContainer(
                                          transaction:
                                              state.feesTransactions[index],
                                        ),
                                      );
                                    }
                                  })..addAll([
                                    if (state.moreTransactionsFetchInProgress)
                                      _buildOneTransactionShimmerLoader(),
                                    if (state.moreTransactionsFetchError &&
                                        !state.moreTransactionsFetchInProgress)
                                      LoadMoreErrorWidget(
                                        onTapRetry: () {
                                          context
                                              .read<FeesTransactionsCubit>()
                                              .fetchMoreFeesTransactions(
                                                isStudentLoggedIn: !context
                                                    .read<AuthCubit>()
                                                    .isParent(),
                                              );
                                        },
                                      ),
                                  ]),
                            ),
                          ),
                        ),
                      );
              }
              if (state is FeesTransactionFetchFailure) {
                return Center(
                  child: ErrorContainer(
                    errorMessageCode: state.errorMessage,
                    onTapRetry: () {
                      context
                          .read<FeesTransactionsCubit>()
                          .fetchFeesTransactions(
                            page: 1,
                            isStudentLoggedIn: !context
                                .read<AuthCubit>()
                                .isParent(),
                          );
                    },
                  ),
                );
              }
              return Padding(
                padding: EdgeInsets.only(
                  top: UiUtils.getScrollViewTopPadding(
                    context: context,
                    appBarHeightPercentage:
                        UiUtils.appBarSmallerHeightPercentage,
                  ),
                ),
                child: _buildShimmerLoader(),
              );
            },
          ),
          Align(alignment: Alignment.topCenter, child: _buildAppBar(context)),
        ],
      ),
    );
  }
}
